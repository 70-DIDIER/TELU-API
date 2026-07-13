<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobOffer;
use App\Models\JobSeeker;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    /**
     * List the applications submitted by the authenticated job seeker.
     * Optional filter: ?status=pending|accepted|rejected|completed.
     */
    public function index(Request $request): JsonResponse
    {
        $seeker = $this->seekerOrFail($request);

        if (! $seeker instanceof JobSeeker) {
            return $seeker; // JsonResponse with the 403 error.
        }

        $applications = $seeker->applications()
            ->with('jobOffer:id,title,location,daily_rate,recruiter_id')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('applied_at')
            ->paginate(20);

        return response()->json($applications);
    }

    /**
     * Apply to an active job offer as the authenticated job seeker.
     */
    public function apply(Request $request, string $jobOffer): JsonResponse
    {
        $seeker = $this->seekerOrFail($request);

        if (! $seeker instanceof JobSeeker) {
            return $seeker;
        }

        $offer = JobOffer::query()->where('is_active', true)->find($jobOffer);

        if (! $offer) {
            return response()->json(['message' => 'Offre introuvable ou clôturée.'], 404);
        }

        $already = $seeker->applications()->where('job_offer_id', $offer->id)->exists();

        if ($already) {
            return response()->json(['message' => 'Vous avez déjà postulé à cette offre.'], 409);
        }

        $application = $seeker->applications()->create([
            'job_offer_id' => $offer->id,
            'status' => 'pending',
            'applied_at' => now(),
        ]);

        // Notify the recruiter of the new application.
        Notifier::send(
            $offer->recruiter->user_id,
            'job',
            "Nouvelle candidature reçue pour « {$offer->title} »."
        );

        return response()->json(
            $application->load('jobOffer:id,title,location,daily_rate,recruiter_id'),
            201
        );
    }

    /**
     * Withdraw one of the authenticated job seeker's pending applications.
     */
    public function withdraw(Request $request, string $application): JsonResponse
    {
        $seeker = $this->seekerOrFail($request);

        if (! $seeker instanceof JobSeeker) {
            return $seeker;
        }

        $found = $seeker->applications()->with('jobOffer')->find($application);

        if (! $found) {
            return response()->json(['message' => 'Candidature introuvable.'], 404);
        }

        if ($found->status !== 'pending') {
            return response()->json([
                'message' => 'Seule une candidature en attente peut être retirée.',
            ], 422);
        }

        $found->delete();

        // Notify the recruiter that the candidate withdrew.
        Notifier::send(
            $found->jobOffer->recruiter->user_id,
            'job',
            "Une candidature a été retirée pour « {$found->jobOffer->title} »."
        );

        return response()->json(['message' => 'Candidature retirée.']);
    }

    /**
     * Resolve the authenticated user's job-seeker profile, or a 403 JsonResponse
     * when the account has no job-seeker profile yet.
     */
    private function seekerOrFail(Request $request): JsonResponse|JobSeeker
    {
        $seeker = $request->user()->jobSeeker;

        if (! $seeker) {
            return response()->json([
                'message' => 'Vous devez d\'abord créer votre profil de chercheur d\'emploi.',
            ], 403);
        }

        return $seeker;
    }
}
