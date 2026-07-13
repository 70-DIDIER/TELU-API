<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobOfferRequest;
use App\Models\Recruiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruiterJobOfferController extends Controller
{
    /**
     * List the job offers owned by the authenticated recruiter.
     */
    public function index(Request $request): JsonResponse
    {
        $recruiter = $this->recruiterOrFail($request);

        if (! $recruiter instanceof Recruiter) {
            return $recruiter; // JsonResponse with the 403 error.
        }

        return response()->json(
            $recruiter->jobOffers()->latest()->paginate(20)
        );
    }

    /**
     * Create a job offer for the authenticated recruiter.
     */
    public function store(JobOfferRequest $request): JsonResponse
    {
        $recruiter = $this->recruiterOrFail($request);

        if (! $recruiter instanceof Recruiter) {
            return $recruiter;
        }

        $offer = $recruiter->jobOffers()->create($request->validated());

        return response()->json($offer, 201);
    }

    /**
     * Show one of the authenticated recruiter's job offers.
     */
    public function show(Request $request, string $jobOffer): JsonResponse
    {
        $recruiter = $this->recruiterOrFail($request);

        if (! $recruiter instanceof Recruiter) {
            return $recruiter;
        }

        $found = $recruiter->jobOffers()->find($jobOffer);

        if (! $found) {
            return response()->json(['message' => 'Offre introuvable ou non autorisée.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Update one of the authenticated recruiter's job offers.
     */
    public function update(JobOfferRequest $request, string $jobOffer): JsonResponse
    {
        $recruiter = $this->recruiterOrFail($request);

        if (! $recruiter instanceof Recruiter) {
            return $recruiter;
        }

        $found = $recruiter->jobOffers()->find($jobOffer);

        if (! $found) {
            return response()->json(['message' => 'Offre introuvable ou non autorisée.'], 404);
        }

        $found->update($request->validated());

        return response()->json($found);
    }

    /**
     * Delete one of the authenticated recruiter's job offers.
     */
    public function destroy(Request $request, string $jobOffer): JsonResponse
    {
        $recruiter = $this->recruiterOrFail($request);

        if (! $recruiter instanceof Recruiter) {
            return $recruiter;
        }

        $found = $recruiter->jobOffers()->find($jobOffer);

        if (! $found) {
            return response()->json(['message' => 'Offre introuvable ou non autorisée.'], 404);
        }

        $found->delete();

        return response()->json(['message' => 'Offre supprimée.']);
    }

    /**
     * Resolve the authenticated user's recruiter profile, or a 403 JsonResponse
     * when the account has no recruiter profile yet.
     */
    private function recruiterOrFail(Request $request): Recruiter|JsonResponse
    {
        $recruiter = $request->user()->recruiter;

        if (! $recruiter) {
            return response()->json([
                'message' => 'Vous devez d\'abord créer votre profil recruteur.',
            ], 403);
        }

        return $recruiter;
    }
}
