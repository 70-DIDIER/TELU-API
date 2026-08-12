<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPropertyOwnerController extends Controller
{
    /**
     * Paginated list of every property owner.
     * Filters: ?owner_type=, ?search= (company_name).
     */
    public function index(Request $request): JsonResponse
    {
        $owners = PropertyOwner::query()
            ->with(['user:id,full_name,phone,email,status', 'subscription:id,name'])
            ->withCount(['properties', 'reservations'])
            ->when($request->filled('owner_type'), fn ($q) => $q->where('owner_type', $request->string('owner_type')))
            ->when($request->filled('search'), fn ($q) => $q->where('company_name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(20);

        return response()->json($owners);
    }

    /**
     * Show one property owner with user and activity counts.
     */
    public function show(string $owner): JsonResponse
    {
        $found = PropertyOwner::query()
            ->with(['user:id,full_name,phone,email,status', 'subscription:id,name'])
            ->withCount(['properties', 'reservations'])
            ->find($owner);

        if (! $found) {
            return response()->json(['message' => 'Propriétaire introuvable.'], 404);
        }

        return response()->json($found);
    }
}
