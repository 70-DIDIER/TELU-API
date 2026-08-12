<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Browse/search the public catalogue of available properties.
     *
     * Supported query params: search, property_type, price_unit, owner_id,
     * min_price, max_price, bedrooms.
     */
    public function index(Request $request): JsonResponse
    {
        $now = now()->toDateTimeString();

        $properties = Property::query()
            ->where('is_available', true)
            ->with('owner:id,user_id,company_name,owner_type,subscription_id,subscription_expires_at')
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('property_type'), fn ($q) => $q->where('property_type', $request->string('property_type')))
            ->when($request->filled('price_unit'), fn ($q) => $q->where('price_unit', $request->string('price_unit')))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->string('owner_id')))
            ->when($request->filled('min_price'), fn ($q) => $q->where('price', '>=', $request->float('min_price')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', $request->float('max_price')))
            ->when($request->filled('bedrooms'), fn ($q) => $q->where('bedrooms', '>=', $request->integer('bedrooms')))
            // Listings from an owner with an active subscription are boosted first.
            ->orderByRaw(
                '(select case when po.subscription_expires_at > ? then 0 else 1 end
                    from property_owners po where po.id = properties.owner_id) asc',
                [$now]
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($properties);
    }

    /**
     * Show a single property with its owner.
     */
    public function show(string $property): JsonResponse
    {
        $found = Property::query()
            ->where('is_available', true)
            ->with('owner:id,user_id,company_name,owner_type,subscription_id,subscription_expires_at')
            ->find($property);

        if (! $found) {
            return response()->json(['message' => 'Bien introuvable.'], 404);
        }

        return response()->json($found);
    }
}
