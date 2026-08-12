<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recruiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRecruiterController extends Controller
{
    /**
     * Paginated list of every recruiter.
     * Filters: ?industry=, ?search= (company_name).
     */
    public function index(Request $request): JsonResponse
    {
        $recruiters = Recruiter::query()
            ->with(['user:id,full_name,phone,email,status', 'subscription:id,name'])
            ->withCount(['jobOffers', 'applications'])
            ->when($request->filled('industry'), fn ($q) => $q->where('industry', $request->string('industry')))
            ->when($request->filled('search'), fn ($q) => $q->where('company_name', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(20);

        return response()->json($recruiters);
    }

    /**
     * Show one recruiter with user and activity counts.
     */
    public function show(string $recruiter): JsonResponse
    {
        $found = Recruiter::query()
            ->with(['user:id,full_name,phone,email,status', 'subscription:id,name'])
            ->withCount(['jobOffers', 'applications'])
            ->find($recruiter);

        if (! $found) {
            return response()->json(['message' => 'Recruteur introuvable.'], 404);
        }

        return response()->json($found);
    }
}
