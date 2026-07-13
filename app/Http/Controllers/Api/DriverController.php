<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DriverProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Show the authenticated user's driver profile.
     */
    public function show(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (! $driver) {
            return response()->json(['message' => 'Aucun profil livreur.'], 404);
        }

        return response()->json($driver);
    }

    /**
     * Create the driver profile for the authenticated user.
     */
    public function store(DriverProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== 'driver') {
            return response()->json([
                'message' => 'Seul un compte de type livreur peut créer un profil livreur.',
            ], 403);
        }

        if ($user->driver) {
            return response()->json([
                'message' => 'Ce compte possède déjà un profil livreur.',
            ], 409);
        }

        $driver = $user->driver()->create($request->validated());

        return response()->json($driver, 201);
    }

    /**
     * Update the authenticated user's driver profile.
     */
    public function update(DriverProfileRequest $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if (! $driver) {
            return response()->json(['message' => 'Aucun profil livreur.'], 404);
        }

        $driver->update($request->validated());

        return response()->json($driver);
    }
}
