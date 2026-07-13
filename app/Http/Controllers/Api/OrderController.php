<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
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
            ->with(['vendor:id,shop_name,address', 'items.product:id,name,price'])
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

            $order = Order::create([
                'vendor_id' => $data['vendor_id'],
                'customer_id' => $request->user()->id,
                'status' => 'pending',
                'total_amount' => $total,
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
}
