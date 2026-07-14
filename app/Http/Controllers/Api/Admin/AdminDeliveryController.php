<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDeliveryController extends Controller
{
    /**
     * Paginated list of every delivery across the platform (read-only oversight).
     * Filters: ?status=, ?driver_id=.
     */
    public function index(Request $request): JsonResponse
    {
        $deliveries = Delivery::query()
            ->with([
                'order:id,vendor_id,customer_id,status,total_amount',
                'order.vendor:id,shop_name',
                'driver.user:id,full_name,phone',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('driver_id'), fn ($q) => $q->where('driver_id', $request->string('driver_id')))
            ->latest()
            ->paginate(20);

        return response()->json($deliveries);
    }

    /**
     * Show one delivery with its order and assigned driver.
     */
    public function show(string $delivery): JsonResponse
    {
        $found = Delivery::query()
            ->with([
                'order.vendor:id,shop_name',
                'order.customer:id,full_name,phone',
                'driver.user:id,full_name,phone',
            ])
            ->find($delivery);

        if (! $found) {
            return response()->json(['message' => 'Livraison introuvable.'], 404);
        }

        return response()->json($found);
    }
}
