<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRatingController extends Controller
{
    /**
     * Paginated list of every rating across the platform (moderation).
     * Filters: ?target_type=, ?score=, ?rater_id=, ?has_comment=1.
     */
    public function index(Request $request): JsonResponse
    {
        $ratings = Rating::query()
            ->with('rater:id,full_name,phone')
            ->when($request->filled('target_type'), fn ($q) => $q->where('target_type', $request->string('target_type')))
            ->when($request->filled('score'), fn ($q) => $q->where('score', (int) $request->integer('score')))
            ->when($request->filled('rater_id'), fn ($q) => $q->where('rater_id', $request->string('rater_id')))
            ->when($request->boolean('has_comment'), fn ($q) => $q->whereNotNull('comment')->where('comment', '!=', ''))
            ->latest()
            ->paginate(20);

        return response()->json($ratings);
    }

    /**
     * Show one rating with its author.
     */
    public function show(string $rating): JsonResponse
    {
        $found = Rating::query()
            ->with('rater:id,full_name,phone,email')
            ->find($rating);

        if (! $found) {
            return response()->json(['message' => 'Évaluation introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Delete an abusive/inappropriate rating (moderation).
     */
    public function destroy(string $rating): JsonResponse
    {
        $found = Rating::find($rating);

        if (! $found) {
            return response()->json(['message' => 'Évaluation introuvable.'], 404);
        }

        $found->delete();

        return response()->json(['message' => 'Évaluation supprimée.']);
    }
}
