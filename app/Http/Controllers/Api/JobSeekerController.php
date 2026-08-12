<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\JobSeekerProfileRequest;
use App\Models\JobSeeker;
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

        // Le compte client est standard : n'importe quel compte non-admin peut
        // compléter son compte d'un profil chercheur d'emploi (cumul autorisé).
        if ($user->user_type === 'admin') {
            return response()->json([
                'message' => 'Un compte administrateur ne peut pas créer ce profil.',
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

    /**
     * Browse/search the public directory of job seekers ("find talent").
     *
     * Supported query params: search (profession), availability.
     */
    public function browse(Request $request): JsonResponse
    {
        $seekers = JobSeeker::query()
            ->with('user:id,full_name,profile_photo,current_latitude,current_longitude')
            ->when($request->filled('search'), fn ($q) => $q->where('profession', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('availability'), fn ($q) => $q->where('availability', 'like', '%'.$request->string('availability').'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($seekers);
    }

    /**
     * Show a single job seeker's public profile.
     */
    public function showPublic(string $jobSeeker): JsonResponse
    {
        $found = JobSeeker::query()
            ->with('user:id,full_name,profile_photo,current_latitude,current_longitude')
            ->find($jobSeeker);

        if (! $found) {
            return response()->json(['message' => 'Profil introuvable.'], 404);
        }

        return response()->json($found);
    }
}
