<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\CommerceLedger;
use App\Services\Notifier;
use App\Support\Geo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Fallback defaults used only if the corresponding backoffice Setting row
     * is missing (see database\seeders\SettingSeeder) — admins tune the real
     * values via GET/PATCH /api/admin/settings, no redeploy needed.
     */
    public const DEFAULT_BASE_FEE = 300.0;

    public const DEFAULT_RATE_PER_KM = 150.0;

    public const DEFAULT_MIN_FEE = 500.0;

    public const DEFAULT_MAX_FEE = 5000.0;

    public const DEFAULT_COMMISSION_RATE = 0.10;

    /**
     * List the orders placed by the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with('vendor:id,shop_name')
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    /**
     * Show one of the authenticated customer's orders.
     */
    public function show(Request $request, string $order): JsonResponse
    {
        $found = $request->user()
            ->orders()
            ->with(['vendor:id,user_id,shop_name,address', 'items.product:id,name,price'])
            ->find($order);

        if (! $found) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Confirm receipt of an order that is out for delivery. Closes both the
     * order and its delivery, and notifies the vendor and the driver.
     */
    public function confirmReceipt(Request $request, string $order): JsonResponse
    {
        $found = $request->user()->orders()->find($order);

        if (! $found) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        if ($found->status !== 'in_delivery') {
            return response()->json([
                'message' => 'Seule une commande en cours de livraison peut être confirmée.',
            ], 422);
        }

        DB::transaction(function () use ($found) {
            $found->update(['status' => 'delivered']);

            $delivery = $found->delivery()->with('driver')->first();

            if ($delivery) {
                $delivery->update([
                    'status' => 'delivered',
                    'delivery_time' => now(),
                ]);

                // Notify the driver that the customer confirmed receipt.
                if ($delivery->driver) {
                    Notifier::send(
                        $delivery->driver->user_id,
                        'delivery',
                        'Livraison confirmée par le client.'
                    );
                }
            }

            // Notify the vendor that the order was delivered.
            Notifier::send(
                $found->vendor->user_id,
                'order',
                'Votre commande a été livrée et confirmée par le client.'
            );
        });

        // Credits the vendor/driver wallets if a successful payment already
        // exists for this order (otherwise it will settle later, from
        // PaymentController::applyGatewayStatus() once the payment succeeds).
        CommerceLedger::settleOrderIfReady($found->fresh());

        return response()->json($found->fresh()->load('delivery'));
    }

    /**
     * Place a new order. The total is computed server-side from the current
     * product prices; every item must belong to the same vendor, be available
     * and be in stock.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $order = DB::transaction(function () use ($request, $data) {
            $vendor = Vendor::find($data['vendor_id']);

            // Load only the requested products that actually belong to the vendor.
            $requestedIds = collect($data['items'])->pluck('product_id')->unique();

            $products = Product::query()
                ->where('vendor_id', $data['vendor_id'])
                ->whereIn('id', $requestedIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $lines = [];

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ["Le produit {$item['product_id']} n'appartient pas à ce vendeur."],
                    ]);
                }

                if (! $product->is_available) {
                    throw ValidationException::withMessages([
                        'items' => ["Le produit « {$product->name} » n'est pas disponible."],
                    ]);
                }

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Stock insuffisant pour « {$product->name} » (disponible : {$product->stock})."],
                    ]);
                }

                $total += (float) $product->price * $item['quantity'];
                $lines[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                ];
            }

            $deliveryFee = $this->calculateDeliveryFee(
                $vendor?->latitude !== null ? (float) $vendor->latitude : null,
                $vendor?->longitude !== null ? (float) $vendor->longitude : null,
                isset($data['delivery_latitude']) ? (float) $data['delivery_latitude'] : null,
                isset($data['delivery_longitude']) ? (float) $data['delivery_longitude'] : null,
            );

            $commissionRate = (float) Setting::get('commission_rate_order', self::DEFAULT_COMMISSION_RATE);
            $commissionAmount = round($total * $commissionRate, 2);
            $vendorNetAmount = round($total - $commissionAmount, 2);

            $order = Order::create([
                'vendor_id' => $data['vendor_id'],
                'customer_id' => $request->user()->id,
                'status' => 'pending',
                'total_amount' => $total + $deliveryFee,
                'delivery_fee' => $deliveryFee,
                'commission_amount' => $commissionAmount,
                'vendor_net_amount' => $vendorNetAmount,
                'delivery_address' => $data['delivery_address'],
                'delivery_latitude' => $data['delivery_latitude'] ?? null,
                'delivery_longitude' => $data['delivery_longitude'] ?? null,
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        // Notify the vendor that a new order has arrived.
        Notifier::send(
            $order->vendor->user_id,
            'order',
            "Nouvelle commande reçue (montant : {$order->total_amount})."
        );

        return response()->json(
            $order->load('items.product:id,name,price'),
            201
        );
    }

    /**
     * frais = delivery_base_fee + distance_km × delivery_rate_per_km, bounded
     * by [delivery_min_fee, delivery_max_fee] — all backoffice-editable
     * (Setting::get). Falls back to the floor fee when either point is
     * missing GPS coordinates.
     */
    private function calculateDeliveryFee(?float $vendorLat, ?float $vendorLng, ?float $deliveryLat, ?float $deliveryLng): float
    {
        $minFee = (float) Setting::get('delivery_min_fee', self::DEFAULT_MIN_FEE);

        if ($vendorLat === null || $vendorLng === null || $deliveryLat === null || $deliveryLng === null) {
            return $minFee;
        }

        $baseFee = (float) Setting::get('delivery_base_fee', self::DEFAULT_BASE_FEE);
        $ratePerKm = (float) Setting::get('delivery_rate_per_km', self::DEFAULT_RATE_PER_KM);
        $maxFee = (float) Setting::get('delivery_max_fee', self::DEFAULT_MAX_FEE);

        $distanceKm = Geo::distanceKm($vendorLat, $vendorLng, $deliveryLat, $deliveryLng);
        $fee = max($baseFee + ($distanceKm * $ratePerKm), $minFee);

        if ($maxFee > 0) {
            $fee = min($fee, $maxFee);
        }

        return round($fee, 2);
    }
}
