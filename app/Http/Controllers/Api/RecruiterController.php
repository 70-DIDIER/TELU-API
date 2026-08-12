<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\RecruiterProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruiterController extends Controller
{
    /**
     * Show the authenticated user's recruiter profile.
     */
    public function show(Request $request): JsonResponse
    {
        $recruiter = $request->user()->recruiter;

        if (! $recruiter) {
            return response()->json(['message' => 'Aucun profil recruteur.'], 404);
        }

        return response()->json($recruiter);
    }

    /**
     * Create the recruiter profile for the authenticated user.
     */
    public function store(RecruiterProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        // Le compte client est standard : n'importe quel compte non-admin peut
        // compléter son compte d'un profil recruteur (cumul de profils autorisé).
        if ($user->user_type === 'admin') {
            return response()->json([
                'message' => 'Un compte administrateur ne peut pas créer de profil recruteur.',
            ], 403);
        }

        if ($user->recruiter) {
            return response()->json([
                'message' => 'Ce compte possède déjà un profil recruteur.',
            ], 409);
        }

        $recruiter = $user->recruiter()->create($request->validated());

        return response()->json($recruiter, 201);
    }

    /**
     * Update the authenticated user's recruiter profile.
     */
    public function update(RecruiterProfileRequest $request): JsonResponse
    {
        $recruiter = $request->user()->recruiter;

        if (! $recruiter) {
            return response()->json(['message' => 'Aucun profil recruteur.'], 404);
        }

        $recruiter->update($request->validated());

        return response()->json($recruiter);
    }
}
