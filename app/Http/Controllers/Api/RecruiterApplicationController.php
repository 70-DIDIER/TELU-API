<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Models\Recruiter;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruiterApplicationController extends Controller
{
    /**
     * Allowed status transitions for an application, driven by the recruiter.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'pending' => ['accepted', 'rejected'],
        'accepted' => ['completed'],
    ];

    /**
     * List the applications for one of the authenticated recruiter's offers.
     * Optional filter: ?status=pending|accepted|rejected|completed.
     */
    public function index(Request $request, string $jobOffer): JsonResponse
    {
        $recruiter = $this->recruiterOrFail($request);

        if (! $recruiter instanceof Recruiter) {
            return $recruiter;
        }

        $offer = $recruiter->jobOffers()->find($jobOffer);

        if (! $offer) {
            return response()->json(['message' => 'Offre introuvable ou non autorisée.'], 404);
        }

        $applications = $offer->applications()
            ->with('jobSeeker:id,profession,skills,experience,availability')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest('applied_at')
            ->paginate(20);

        return response()->json($applications);
    }

    /**
     * Update the status of an application on one of the recruiter's offers.
     */
    public function updateStatus(UpdateApplicationStatusRequest $request, string $application): JsonResponse
    {
        $recruiter = $this->recruiterOrFail($request);

        if (! $recruiter instanceof Recruiter) {
            return $recruiter;
        }

        $found = $recruiter->applications()->with('jobSeeker')->find($application);

        if (! $found) {
            return response()->json(['message' => 'Candidature introuvable ou non autorisée.'], 404);
        }

        $target = $request->validated()['status'];
        $allowed = self::TRANSITIONS[$found->status] ?? [];

        if (! in_array($target, $allowed, true)) {
            return response()->json([
                'message' => "Transition non autorisée : {$found->status} → {$target}.",
            ], 422);
        }

        $found->update(['status' => $target]);

        // Notify the job seeker of the new application status.
        Notifier::send(
            $found->jobSeeker->user_id,
            'job',
            "Votre candidature est maintenant : {$target}."
        );

        return response()->json($found->fresh()->load('jobSeeker:id,profession,skills'));
    }

    /**
     * Resolve the authenticated user's recruiter profile, or a 403 JsonResponse
     * when the account has no recruiter profile yet.
     */
    private function recruiterOrFail(Request $request): JsonResponse|Recruiter
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
