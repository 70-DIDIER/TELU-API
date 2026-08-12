<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobOfferController extends Controller
{
    /**
     * Browse/search the public board of active job offers.
     *
     * Supported query params: search, location, recruiter_id,
     * min_rate, max_rate.
     */
    public function index(Request $request): JsonResponse
    {
        $now = now()->toDateTimeString();

        $offers = JobOffer::query()
            ->where('is_active', true)
            ->with('recruiter:id,user_id,company_name,industry,subscription_id,subscription_expires_at')
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('location'), fn ($q) => $q->where('location', 'like', '%'.$request->string('location').'%'))
            ->when($request->filled('recruiter_id'), fn ($q) => $q->where('recruiter_id', $request->string('recruiter_id')))
            ->when($request->filled('min_rate'), fn ($q) => $q->where('daily_rate', '>=', $request->float('min_rate')))
            ->when($request->filled('max_rate'), fn ($q) => $q->where('daily_rate', '<=', $request->float('max_rate')))
            // Offers from a recruiter with an active subscription are boosted first.
            ->orderByRaw(
                '(select case when r.subscription_expires_at > ? then 0 else 1 end
                    from recruiters r where r.id = job_offers.recruiter_id) asc',
                [$now]
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($offers);
    }

    /**
     * Show a single active job offer with its recruiter.
     */
    public function show(string $jobOffer): JsonResponse
    {
        $found = JobOffer::query()
            ->where('is_active', true)
            ->with('recruiter:id,user_id,company_name,industry,subscription_id,subscription_expires_at')
            ->find($jobOffer);

        if (! $found) {
            return response()->json(['message' => 'Offre introuvable.'], 404);
        }

        return response()->json($found);
    }
}
