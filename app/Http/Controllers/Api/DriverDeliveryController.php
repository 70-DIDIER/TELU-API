<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Driver;
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
            ->with(['order:id,vendor_id,delivery_address,total_amount', 'order.vendor:id,shop_name,address'])
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
            ->with(['order:id,vendor_id,customer_id,delivery_address,total_amount,status'])
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

            $found->loadMissing('order');
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
