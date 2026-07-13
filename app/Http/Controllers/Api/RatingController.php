<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Driver;
use App\Models\JobSeeker;
use App\Models\PropertyOwner;
use App\Models\Rating;
use App\Models\Recruiter;
use App\Models\Vendor;
use App\Services\Notifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Rated-profile model per target_type.
     *
     * @var array<string, class-string<Model>>
     */
    private const TARGET_MODELS = [
        'vendor' => Vendor::class,
        'driver' => Driver::class,
        'property_owner' => PropertyOwner::class,
        'recruiter' => Recruiter::class,
        'job_seeker' => JobSeeker::class,
    ];

    /**
     * Public ratings for a target profile, with average score and count.
     */
    public function forTarget(string $targetType, string $targetId): JsonResponse
    {
        if (! array_key_exists($targetType, self::TARGET_MODELS)) {
            return response()->json(['message' => 'Type de cible invalide.'], 404);
        }

        $base = Rating::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId);

        $ratings = (clone $base)
            ->with('rater:id,full_name,profile_photo')
            ->latest()
            ->paginate(20);

        return response()->json([
            'target_type' => $targetType,
            'target_id' => $targetId,
            'average' => round((float) (clone $base)->avg('score'), 2),
            'count' => (clone $base)->count(),
            'ratings' => $ratings,
        ]);
    }

    /**
     * Ratings left by the authenticated user.
     */
    public function mine(Request $request): JsonResponse
    {
        $ratings = $request->user()
            ->ratingsGiven()
            ->latest()
            ->paginate(20);

        return response()->json($ratings);
    }

    /**
     * Leave a rating for a business profile. One rating per rater per target;
     * a user cannot rate their own profile.
     */
    public function store(StoreRatingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $rater = $request->user();

        $modelClass = self::TARGET_MODELS[$data['target_type']];
        $target = $modelClass::find($data['target_id']);

        if (! $target) {
            return response()->json(['message' => 'Profil cible introuvable.'], 404);
        }

        if ($target->user_id === $rater->id) {
            return response()->json(['message' => 'Vous ne pouvez pas vous auto-évaluer.'], 422);
        }

        $already = Rating::query()
            ->where('rater_id', $rater->id)
            ->where('target_type', $data['target_type'])
            ->where('target_id', $data['target_id'])
            ->exists();

        if ($already) {
            return response()->json(['message' => 'Vous avez déjà évalué ce profil.'], 409);
        }

        $rating = Rating::create([
            'rater_id' => $rater->id,
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'score' => $data['score'],
            'comment' => $data['comment'] ?? null,
        ]);

        // Notify the rated user that they received a new rating.
        Notifier::send(
            $target->user_id,
            'rating',
            "Vous avez reçu une nouvelle évaluation ({$rating->score}/5)."
        );

        return response()->json($rating, 201);
    }
}
