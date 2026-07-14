<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    /**
     * Paginated list of every order across the platform (read-only oversight).
     * Filters: ?status=, ?vendor_id=, ?customer_id=.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with([
                'vendor:id,shop_name',
                'customer:id,full_name,phone',
            ])
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->string('vendor_id')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->string('customer_id')))
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    /**
     * Show one order with items, customer, vendor and its delivery.
     */
    public function show(string $order): JsonResponse
    {
        $found = Order::query()
            ->with([
                'vendor:id,shop_name',
                'customer:id,full_name,phone,email',
                'items.product:id,name,price',
                'delivery.driver:id,user_id',
            ])
            ->find($order);

        if (! $found) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        return response()->json($found);
    }
}
