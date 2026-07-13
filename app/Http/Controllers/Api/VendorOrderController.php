<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Vendor;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorOrderController extends Controller
{
    /**
     * Allowed status transitions a vendor may perform.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'pending' => ['accepted', 'cancelled'],
        'accepted' => ['preparing', 'cancelled'],
        'preparing' => ['cancelled'],
    ];

    /**
     * Statuses in which the stock has already been deducted for the order.
     *
     * @var list<string>
     */
    private const STOCK_HELD = ['accepted', 'preparing', 'in_delivery', 'delivered'];

    /**
     * List the orders received by the authenticated vendor (optional ?status=).
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendorOrFail($request);

        if (! $vendor instanceof Vendor) {
            return $vendor;
        }

        $orders = $vendor->orders()
            ->with(['customer:id,full_name,phone', 'items:id,order_id,product_id,quantity,unit_price'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    /**
     * Show one of the authenticated vendor's orders.
     */
    public function show(Request $request, string $order): JsonResponse
    {
        $vendor = $this->vendorOrFail($request);

        if (! $vendor instanceof Vendor) {
            return $vendor;
        }

        $found = $vendor->orders()
            ->with(['customer:id,full_name,phone', 'items.product:id,name,price'])
            ->find($order);

        if (! $found) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Move one of the vendor's orders to a new status, adjusting stock when the
     * order is accepted (deduct) or cancelled after acceptance (restore).
     */
    public function updateStatus(UpdateOrderStatusRequest $request, string $order): JsonResponse
    {
        $vendor = $this->vendorOrFail($request);

        if (! $vendor instanceof Vendor) {
            return $vendor;
        }

        $target = $request->validated()['status'];

        $updated = DB::transaction(function () use ($vendor, $order, $target) {
            $found = $vendor->orders()->lockForUpdate()->find($order);

            if (! $found) {
                return null;
            }

            $allowed = self::TRANSITIONS[$found->status] ?? [];

            if (! \in_array($target, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => ["Transition invalide : « {$found->status} » → « {$target} »."],
                ]);
            }

            if ($target === 'accepted') {
                $this->deductStock($found);
                $this->openDelivery($found);
            } elseif ($target === 'cancelled' && \in_array($found->status, self::STOCK_HELD, true)) {
                $this->restoreStock($found);
            }

            $found->update(['status' => $target]);

            return $found;
        });

        if ($updated === null) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        return response()->json($updated->load('items.product:id,name,stock'));
    }

    /**
     * Deduct ordered quantities from stock, re-checking availability.
     */
    private function deductStock(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product) {
                continue;
            }

            if ($product->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'status' => ["Stock insuffisant pour « {$product->name} » (disponible : {$product->stock})."],
                ]);
            }

            $product->decrement('stock', $item->quantity);
        }
    }

    /**
     * Give back the ordered quantities to stock.
     */
    private function restoreStock(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            $item->product?->increment('stock', $item->quantity);
        }
    }

    /**
     * Open a delivery for the accepted order and notify available drivers.
     */
    private function openDelivery(Order $order): void
    {
        // Idempotent: one delivery per order.
        $order->delivery()->firstOrCreate([], [
            'status' => 'awaiting_driver',
            'delivery_fee' => 0,
        ]);

        $driverUserIds = Driver::query()
            ->where('is_available', true)
            ->pluck('user_id');

        Notifier::sendMany(
            $driverUserIds,
            'delivery',
            'Nouvelle livraison disponible.'
        );
    }

    /**
     * Resolve the authenticated user's vendor profile, or a 403 JsonResponse.
     */
    private function vendorOrFail(Request $request): Vendor|JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (! $vendor) {
            return response()->json([
                'message' => 'Vous devez d\'abord créer votre profil vendeur.',
            ], 403);
        }

        return $vendor;
    }
}
