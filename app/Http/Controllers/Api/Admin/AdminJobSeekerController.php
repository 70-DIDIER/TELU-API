<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobSeeker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminJobSeekerController extends Controller
{
    /**
     * Paginated list of every job seeker.
     * Filters: ?availability=, ?search= (profession).
     */
    public function index(Request $request): JsonResponse
    {
        $seekers = JobSeeker::query()
            ->with('user:id,full_name,phone,email,status')
            ->withCount('applications')
            ->when($request->filled('availability'), fn ($q) => $q->where('availability', $request->string('availability')))
            ->when($request->filled('search'), fn ($q) => $q->where('profession', 'like', '%'.$request->string('search').'%'))
            ->latest()
            ->paginate(20);

        return response()->json($seekers);
    }

    /**
     * Show one job seeker with user and activity counts.
     */
    public function show(string $seeker): JsonResponse
    {
        $found = JobSeeker::query()
            ->with('user:id,full_name,phone,email,status')
            ->withCount('applications')
            ->find($seeker);

        if (! $found) {
            return response()->json(['message' => 'Chercheur d\'emploi introuvable.'], 404);
        }

        return response()->json($found);
    }
}
