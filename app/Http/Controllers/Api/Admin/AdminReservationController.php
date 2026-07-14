<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReservationController extends Controller
{
    /**
     * Paginated list of every reservation across the platform (read-only oversight).
     * Filters: ?status=, ?property_id=, ?customer_id=.
     */
    public function index(Request $request): JsonResponse
    {
        $reservations = Reservation::query()
            ->with([
                'property:id,title,owner_id',
                'property.owner:id,company_name',
                'customer:id,full_name,phone',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('property_id'), fn ($q) => $q->where('property_id', $request->string('property_id')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->string('customer_id')))
            ->latest()
            ->paginate(20);

        return response()->json($reservations);
    }

    /**
     * Show one reservation with its property, owner and customer.
     */
    public function show(string $reservation): JsonResponse
    {
        $found = Reservation::query()
            ->with([
                'property:id,title,owner_id,price,price_unit',
                'property.owner:id,company_name',
                'customer:id,full_name,phone,email',
            ])
            ->find($reservation);

        if (! $found) {
            return response()->json(['message' => 'Réservation introuvable.'], 404);
        }

        return response()->json($found);
    }
}
