<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminJobOfferController extends Controller
{
    /**
     * Paginated list of every job offer across all recruiters.
     * Filters: ?recruiter_id=, ?location=, ?is_active=, ?search= (title).
     */
    public function index(Request $request): JsonResponse
    {
        $offers = JobOffer::query()
            ->with('recruiter:id,company_name')
            ->withCount('applications')
            ->when($request->filled('recruiter_id'), fn ($q) => $q->where('recruiter_id', $request->string('recruiter_id')))
            ->when($request->filled('location'), fn ($q) => $q->where('location', 'like', '%'.$request->string('location').'%'))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(20);

        return response()->json($offers);
    }

    /**
     * Show one job offer with its recruiter.
     */
    public function show(string $jobOffer): JsonResponse
    {
        $found = JobOffer::query()
            ->with('recruiter:id,company_name')
            ->withCount('applications')
            ->find($jobOffer);

        if (! $found) {
            return response()->json(['message' => 'Offre introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Take a job offer down or back up (moderation of the board).
     */
    public function updateStatus(Request $request, string $jobOffer): JsonResponse
    {
        $data = $request->validate(['is_active' => ['required', 'boolean']]);

        $found = JobOffer::find($jobOffer);

        if (! $found) {
            return response()->json(['message' => 'Offre introuvable.'], 404);
        }

        $found->update(['is_active' => $data['is_active']]);

        return response()->json($found);
    }
}
