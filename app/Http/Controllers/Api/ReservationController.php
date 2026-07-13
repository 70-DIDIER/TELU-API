<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    /**
     * List the reservations made by the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $reservations = $request->user()
            ->reservations()
            ->with('property:id,title,property_type,address,price,price_unit')
            ->latest()
            ->paginate(20);

        return response()->json($reservations);
    }

    /**
     * Show one of the authenticated customer's reservations.
     */
    public function show(Request $request, string $reservation): JsonResponse
    {
        $found = $request->user()
            ->reservations()
            ->with('property:id,title,property_type,address,price,price_unit')
            ->find($reservation);

        if (! $found) {
            return response()->json(['message' => 'Réservation introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Create a reservation. The total price is computed server-side from the
     * property's current price and the booked duration; overlapping active
     * reservations for the same property are rejected.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $checkIn = Carbon::parse($data['check_in'])->startOfDay();
        $checkOut = Carbon::parse($data['check_out'])->startOfDay();

        $reservation = DB::transaction(function () use ($request, $data, $checkIn, $checkOut) {
            /** @var Property|null $property */
            $property = Property::query()
                ->whereKey($data['property_id'])
                ->lockForUpdate()
                ->first();

            if (! $property || ! $property->is_available) {
                throw ValidationException::withMessages([
                    'property_id' => ["Ce bien n'est pas disponible à la réservation."],
                ]);
            }

            // Reject any overlap with an active (pending/confirmed) reservation.
            // Half-open intervals: the check-out day is free for the next guest.
            $overlaps = $property->reservations()
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn)
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages([
                    'check_in' => ['Ce bien est déjà réservé sur cette période.'],
                ]);
            }

            $units = $this->billableUnits($checkIn, $checkOut, $property->price_unit);
            $total = (float) $property->price * $units;

            return Reservation::create([
                'property_id' => $property->id,
                'customer_id' => $request->user()->id,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'total_price' => $total,
                'status' => 'pending',
            ]);
        });

        // Notify the property owner of the new reservation request.
        Notifier::send(
            $reservation->property->owner->user_id,
            'reservation',
            "Nouvelle demande de réservation (montant : {$reservation->total_price})."
        );

        return response()->json(
            $reservation->load('property:id,title,property_type,address,price,price_unit'),
            201
        );
    }

    /**
     * Cancel one of the authenticated customer's own reservations.
     */
    public function cancel(Request $request, string $reservation): JsonResponse
    {
        $found = $request->user()->reservations()->find($reservation);

        if (! $found) {
            return response()->json(['message' => 'Réservation introuvable.'], 404);
        }

        if (! in_array($found->status, ['pending', 'confirmed'], true)) {
            return response()->json([
                'message' => 'Seule une réservation en attente ou confirmée peut être annulée.',
            ], 422);
        }

        $found->update(['status' => 'cancelled']);

        // Notify the owner that the customer cancelled.
        Notifier::send(
            $found->property->owner->user_id,
            'reservation',
            'Une réservation a été annulée par le client.'
        );

        return response()->json($found->fresh());
    }

    /**
     * Number of billable units (nights or months) between two dates for the
     * property's price unit. Always at least one unit.
     */
    private function billableUnits(Carbon $checkIn, Carbon $checkOut, string $priceUnit): int
    {
        $nights = $checkIn->diffInDays($checkOut);

        if ($priceUnit === 'month') {
            return max(1, (int) ceil($nights / 30));
        }

        return max(1, (int) $nights);
    }
}
