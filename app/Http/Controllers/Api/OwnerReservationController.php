<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateReservationStatusRequest;
use App\Models\PropertyOwner;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerReservationController extends Controller
{
    /**
     * Allowed status transitions for a reservation, driven by the owner.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
    ];

    /**
     * List reservations across the authenticated owner's properties.
     * Optional filter: ?status=pending|confirmed|cancelled|completed.
     */
    public function index(Request $request): JsonResponse
    {
        $owner = $this->ownerOrFail($request);

        if (! $owner instanceof PropertyOwner) {
            return $owner;
        }

        $reservations = $owner->reservations()
            ->with(['property:id,title,property_type', 'customer:id,full_name,phone'])
            ->when($request->filled('status'), fn ($q) => $q->where('reservations.status', $request->string('status')))
            ->latest('reservations.created_at')
            ->paginate(20);

        return response()->json($reservations);
    }

    /**
     * Update the status of a reservation on one of the owner's properties.
     */
    public function updateStatus(UpdateReservationStatusRequest $request, string $reservation): JsonResponse
    {
        $owner = $this->ownerOrFail($request);

        if (! $owner instanceof PropertyOwner) {
            return $owner;
        }

        $found = $owner->reservations()->find($reservation);

        if (! $found) {
            return response()->json(['message' => 'Réservation introuvable ou non autorisée.'], 404);
        }

        $target = $request->validated()['status'];
        $allowed = self::TRANSITIONS[$found->status] ?? [];

        if (! in_array($target, $allowed, true)) {
            return response()->json([
                'message' => "Transition non autorisée : {$found->status} → {$target}.",
            ], 422);
        }

        $found->update(['status' => $target]);

        // Notify the customer of the new reservation status.
        Notifier::send(
            $found->customer_id,
            'reservation',
            "Votre réservation est maintenant : {$target}."
        );

        return response()->json($found->fresh()->load('property:id,title,property_type'));
    }

    /**
     * Resolve the authenticated user's property-owner profile, or a 403
     * JsonResponse when the account has no owner profile yet.
     */
    private function ownerOrFail(Request $request): PropertyOwner|JsonResponse
    {
        $owner = $request->user()->propertyOwner;

        if (! $owner) {
            return response()->json([
                'message' => 'Vous devez d\'abord créer votre profil propriétaire.',
            ], 403);
        }

        return $owner;
    }
}
