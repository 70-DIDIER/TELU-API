<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminJobApplicationController extends Controller
{
    /**
     * Paginated list of every job application across the platform (read-only oversight).
     * Filters: ?status=, ?job_offer_id=, ?job_seeker_id=.
     */
    public function index(Request $request): JsonResponse
    {
        $applications = JobApplication::query()
            ->with([
                'jobOffer:id,title,recruiter_id',
                'jobOffer.recruiter:id,company_name',
                'jobSeeker:id,user_id,profession',
                'jobSeeker.user:id,full_name,phone',
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('job_offer_id'), fn ($q) => $q->where('job_offer_id', $request->string('job_offer_id')))
            ->when($request->filled('job_seeker_id'), fn ($q) => $q->where('job_seeker_id', $request->string('job_seeker_id')))
            ->latest()
            ->paginate(20);

        return response()->json($applications);
    }

    /**
     * Show one job application with its offer, recruiter and seeker.
     */
    public function show(string $application): JsonResponse
    {
        $found = JobApplication::query()
            ->with([
                'jobOffer:id,title,recruiter_id,daily_rate',
                'jobOffer.recruiter:id,company_name',
                'jobSeeker:id,user_id,profession',
                'jobSeeker.user:id,full_name,phone,email',
            ])
            ->find($application);

        if (! $found) {
            return response()->json(['message' => 'Candidature introuvable.'], 404);
        }

        return response()->json($found);
    }
}
