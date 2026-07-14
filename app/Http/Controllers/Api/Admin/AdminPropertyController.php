<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPropertyController extends Controller
{
    /**
     * Paginated list of every property across all owners.
     * Filters: ?owner_id=, ?property_type=, ?price_unit=, ?is_available=, ?search= (title).
     */
    public function index(Request $request): JsonResponse
    {
        $properties = Property::query()
            ->with('owner:id,company_name')
            ->withCount('reservations')
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->string('owner_id')))
            ->when($request->filled('property_type'), fn ($q) => $q->where('property_type', $request->string('property_type')))
            ->when($request->filled('price_unit'), fn ($q) => $q->where('price_unit', $request->string('price_unit')))
            ->when($request->has('is_available'), fn ($q) => $q->where('is_available', $request->boolean('is_available')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(20);

        return response()->json($properties);
    }

    /**
     * Show one property with its owner.
     */
    public function show(string $property): JsonResponse
    {
        $found = Property::query()
            ->with('owner:id,company_name')
            ->withCount('reservations')
            ->find($property);

        if (! $found) {
            return response()->json(['message' => 'Bien introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Take a property down or back up (moderation of the listings).
     */
    public function updateAvailability(Request $request, string $property): JsonResponse
    {
        $data = $request->validate(['is_available' => ['required', 'boolean']]);

        $found = Property::find($property);

        if (! $found) {
            return response()->json(['message' => 'Bien introuvable.'], 404);
        }

        $found->update(['is_available' => $data['is_available']]);

        return response()->json($found);
    }
}
