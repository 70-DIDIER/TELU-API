<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\JobSeekerProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobSeekerController extends Controller
{
    /**
     * Show the authenticated user's job-seeker profile.
     */
    public function show(Request $request): JsonResponse
    {
        $seeker = $request->user()->jobSeeker;

        if (! $seeker) {
            return response()->json(['message' => 'Aucun profil chercheur d\'emploi.'], 404);
        }

        return response()->json($seeker);
    }

    /**
     * Create the job-seeker profile for the authenticated user.
     */
    public function store(JobSeekerProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_type !== 'job_seeker') {
            return response()->json([
                'message' => 'Seul un compte de type chercheur d\'emploi peut créer ce profil.',
            ], 403);
        }

        if ($user->jobSeeker) {
            return response()->json([
                'message' => 'Ce compte possède déjà un profil chercheur d\'emploi.',
            ], 409);
        }

        $seeker = $user->jobSeeker()->create($request->validated());

        return response()->json($seeker, 201);
    }

    /**
     * Update the authenticated user's job-seeker profile.
     */
    public function update(JobSeekerProfileRequest $request): JsonResponse
    {
        $seeker = $request->user()->jobSeeker;

        if (! $seeker) {
            return response()->json(['message' => 'Aucun profil chercheur d\'emploi.'], 404);
        }

        $seeker->update($request->validated());

        return response()->json($seeker);
    }
}
