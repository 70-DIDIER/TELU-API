<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Driver;
use App\Services\CommerceLedger;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverDeliveryController extends Controller
{
    /**
     * List deliveries still awaiting a driver (the open pool).
     */
    public function available(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        if (! $driver instanceof Driver) {
            return $driver;
        }

        $deliveries = Delivery::query()
            ->where('status', 'awaiting_driver')
            ->whereNull('driver_id')
            ->with([
                'order:id,vendor_id,delivery_address,delivery_latitude,delivery_longitude,total_amount',
                'order.vendor:id,user_id,shop_name,address,latitude,longitude',
            ])
            ->latest()
            ->paginate(20);

        return response()->json($deliveries);
    }

    /**
     * List the deliveries assigned to the authenticated driver (optional ?status=).
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $this->driver($request);

        if (! $driver instanceof Driver) {
            return $driver;
        }

        $deliveries = $driver->deliveries()
            ->with([
                'order:id,vendor_id,customer_id,delivery_address,delivery_latitude,delivery_longitude,total_amount,status',
                'order.vendor:id,user_id,shop_name,address,latitude,longitude',
                'order.customer:id,full_name,phone',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20);

        return response()->json($deliveries);
    }

    /**
     * Claim an open delivery. Concurrency-safe: only the first driver wins.
     */
    public function claim(Request $request, string $delivery): JsonResponse
    {
        $driver = $this->driver($request);

        if (! $driver instanceof Driver) {
            return $driver;
        }

        $result = DB::transaction(function () use ($driver, $delivery) {
            $found = Delivery::query()->lockForUpdate()->find($delivery);

            if (! $found) {
                return null;
            }

            if ($found->status !== 'awaiting_driver' || $found->driver_id !== null) {
                throw ValidationException::withMessages([
                    'delivery' => ['Cette livraison a déjà été prise par un autre livreur.'],
                ]);
            }

            $found->update([
                'driver_id' => $driver->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            // Notify the vendor that a courier is on the way.
            $found->loadMissing('order.vendor');
            if ($found->order?->vendor) {
                Notifier::send(
                    $found->order->vendor->user_id,
                    'delivery',
                    'Un livreur a accepté la livraison de votre commande.'
                );
            }

            return $found;
        });

        if ($result === null) {
            return response()->json(['message' => 'Livraison introuvable.'], 404);
        }

        return response()->json($result);
    }

    /**
     * Mark an assigned delivery as picked up; the order moves to in_delivery.
     */
    public function pickup(Request $request, string $delivery): JsonResponse
    {
        $driver = $this->driver($request);

        if (! $driver instanceof Driver) {
            return $driver;
        }

        $result = DB::transaction(function () use ($driver, $delivery) {
            $found = $driver->deliveries()->lockForUpdate()->find($delivery);

            if (! $found) {
                return null;
            }

            if ($found->status !== 'assigned') {
                throw ValidationException::withMessages([
                    'delivery' => ["Action impossible depuis le statut « {$found->status} »."],
                ]);
            }

            $found->update([
                'status' => 'picked_up',
                'pickup_time' => now(),
            ]);

            $found->loadMissing([
                'order.vendor:id,user_id,shop_name,address,latitude,longitude',
                'order.customer:id,full_name,phone',
            ]);
            $found->order?->update(['status' => 'in_delivery']);

            // Notify the customer that their order is on the way.
            if ($found->order) {
                Notifier::send(
                    $found->order->customer_id,
                    'delivery',
                    'Votre commande est en cours de livraison.'
                );
            }

            return $found;
        });

        if ($result === null) {
            return response()->json(['message' => 'Livraison introuvable.'], 404);
        }

        return response()->json($result);
    }

    /**
     * Mark a picked-up delivery as delivered. Lets the courier close the
     * delivery on the ground without depending on the customer confirming
     * receipt (which often never happens): the order moves to delivered and,
     * if the order is already paid, the vendor/driver wallets settle. The
     * customer's confirm-receipt endpoint remains an equivalent alternative.
     */
    public function deliver(Request $request, string $delivery): JsonResponse
    {
        $driver = $this->driver($request);

        if (! $driver instanceof Driver) {
            return $driver;
        }

        $result = DB::transaction(function () use ($driver, $delivery) {
            $found = $driver->deliveries()->lockForUpdate()->find($delivery);

            if (! $found) {
                return null;
            }

            if ($found->status !== 'picked_up') {
                throw ValidationException::withMessages([
                    'delivery' => ["Action impossible depuis le statut « {$found->status} »."],
                ]);
            }

            $found->update([
                'status' => 'delivered',
                'delivery_time' => now(),
            ]);

            $found->loadMissing('order.vendor:id,user_id,shop_name');
            $order = $found->order;

            if ($order && $order->status === 'in_delivery') {
                $order->update(['status' => 'delivered']);

                // Notify the customer and the vendor that the order was delivered.
                Notifier::send(
                    $order->customer_id,
                    'delivery',
                    'Votre commande a été livrée. Merci de confirmer la réception si tout est en ordre.'
                );

                if ($order->vendor) {
                    Notifier::send(
                        $order->vendor->user_id,
                        'order',
                        'Votre commande a été livrée par le livreur.'
                    );
                }
            }

            return $found;
        });

        if ($result === null) {
            return response()->json(['message' => 'Livraison introuvable.'], 404);
        }

        // Credits the vendor/driver wallets if a successful payment already
        // exists for this order (idempotent, mirrors OrderController::confirmReceipt).
        if ($result->order) {
            CommerceLedger::settleOrderIfReady($result->order->fresh());
        }

        return response()->json($result->fresh());
    }

    /**
     * Resolve the authenticated user's driver profile, or a 403 JsonResponse.
     */
    private function driver(Request $request): Driver|JsonResponse
    {
        $driver = $request->user()->driver;

        if (! $driver) {
            return response()->json([
                'message' => 'Vous devez d\'abord créer votre profil livreur.',
            ], 403);
        }

        return $driver;
    }
}
