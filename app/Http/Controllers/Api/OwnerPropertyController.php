<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyRequest;
use App\Models\Property;
use App\Models\PropertyOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerPropertyController extends Controller
{
    /**
     * List the properties owned by the authenticated property owner.
     */
    public function index(Request $request): JsonResponse
    {
        $owner = $this->ownerOrFail($request);

        if (! $owner instanceof PropertyOwner) {
            return $owner; // JsonResponse with the 403 error.
        }

        return response()->json(
            $owner->properties()->latest()->paginate(20)
        );
    }

    /**
     * Create a property for the authenticated owner.
     */
    public function store(PropertyRequest $request): JsonResponse
    {
        $owner = $this->ownerOrFail($request);

        if (! $owner instanceof PropertyOwner) {
            return $owner;
        }

        $property = $owner->properties()->create($request->validated());

        return response()->json($property, 201);
    }

    /**
     * Show one of the authenticated owner's properties.
     */
    public function show(Request $request, string $property): JsonResponse
    {
        $owner = $this->ownerOrFail($request);

        if (! $owner instanceof PropertyOwner) {
            return $owner;
        }

        $found = $owner->properties()->find($property);

        if (! $found) {
            return response()->json(['message' => 'Bien introuvable ou non autorisé.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Update one of the authenticated owner's properties.
     */
    public function update(PropertyRequest $request, string $property): JsonResponse
    {
        $owner = $this->ownerOrFail($request);

        if (! $owner instanceof PropertyOwner) {
            return $owner;
        }

        $found = $owner->properties()->find($property);

        if (! $found) {
            return response()->json(['message' => 'Bien introuvable ou non autorisé.'], 404);
        }

        $found->update($request->validated());

        return response()->json($found);
    }

    /**
     * Delete one of the authenticated owner's properties.
     */
    public function destroy(Request $request, string $property): JsonResponse
    {
        $owner = $this->ownerOrFail($request);

        if (! $owner instanceof PropertyOwner) {
            return $owner;
        }

        $found = $owner->properties()->find($property);

        if (! $found) {
            return response()->json(['message' => 'Bien introuvable ou non autorisé.'], 404);
        }

        $found->delete();

        return response()->json(['message' => 'Bien supprimé.']);
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
