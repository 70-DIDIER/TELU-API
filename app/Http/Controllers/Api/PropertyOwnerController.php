<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\PropertyOwnerProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyOwnerController extends Controller
{
    /**
     * Show the authenticated user's property-owner profile.
     */
    public function show(Request $request): JsonResponse
    {
        $owner = $request->user()->propertyOwner;

        if (! $owner) {
            return response()->json(['message' => 'Aucun profil propriétaire.'], 404);
        }

        return response()->json($owner);
    }

    /**
     * Create the property-owner profile for the authenticated user.
     */
    public function store(PropertyOwnerProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        // Le compte client est standard : n'importe quel compte non-admin peut
        // compléter son compte d'un profil propriétaire (cumul de profils autorisé).
        if ($user->user_type === 'admin') {
            return response()->json([
                'message' => 'Un compte administrateur ne peut pas créer de profil propriétaire.',
            ], 403);
        }

        if ($user->propertyOwner) {
            return response()->json([
                'message' => 'Ce compte possède déjà un profil propriétaire.',
            ], 409);
        }

        $owner = $user->propertyOwner()->create($request->validated());

        return response()->json($owner, 201);
    }

    /**
     * Update the authenticated user's property-owner profile.
     */
    public function update(PropertyOwnerProfileRequest $request): JsonResponse
    {
        $owner = $request->user()->propertyOwner;

        if (! $owner) {
            return response()->json(['message' => 'Aucun profil propriétaire.'], 404);
        }

        $owner->update($request->validated());

        return response()->json($owner);
    }
}
