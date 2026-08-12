<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\VendorProfileRequest;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * Show the authenticated user's vendor profile.
     */
    public function show(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (! $vendor) {
            return response()->json(['message' => 'Aucun profil vendeur.'], 404);
        }

        return response()->json($vendor);
    }

    /**
     * Create the vendor profile for the authenticated user.
     */
    public function store(VendorProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        // Le compte client est standard : n'importe quel compte non-admin peut
        // compléter son compte d'un profil vendeur (cumul de profils autorisé).
        if ($user->user_type === 'admin') {
            return response()->json([
                'message' => 'Un compte administrateur ne peut pas créer de profil vendeur.',
            ], 403);
        }

        if ($user->vendor) {
            return response()->json([
                'message' => 'Ce compte possède déjà un profil vendeur.',
            ], 409);
        }

        $vendor = $user->vendor()->create($request->validated());

        return response()->json($vendor, 201);
    }

    /**
     * Update the authenticated user's vendor profile.
     */
    public function update(VendorProfileRequest $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (! $vendor) {
            return response()->json(['message' => 'Aucun profil vendeur.'], 404);
        }

        $vendor->update($request->validated());

        return response()->json($vendor);
    }
}
