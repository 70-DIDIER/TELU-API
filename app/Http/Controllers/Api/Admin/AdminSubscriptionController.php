<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionRequest;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    /**
     * List subscription plans with subscriber counts.
     * Filter: ?subscriber_type=.
     */
    public function index(Request $request): JsonResponse
    {
        $plans = Subscription::query()
            ->withCount(['propertyOwners', 'recruiters'])
            ->when($request->filled('subscriber_type'), fn ($q) => $q->where('subscriber_type', $request->string('subscriber_type')))
            ->latest()
            ->get();

        return response()->json($plans);
    }

    /**
     * Show one plan with subscriber counts.
     */
    public function show(string $subscription): JsonResponse
    {
        $found = Subscription::query()
            ->withCount(['propertyOwners', 'recruiters'])
            ->find($subscription);

        if (! $found) {
            return response()->json(['message' => 'Abonnement introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Create a new subscription plan.
     */
    public function store(SubscriptionRequest $request): JsonResponse
    {
        $plan = Subscription::create($request->validated());

        return response()->json($plan, 201);
    }

    /**
     * Update an existing plan.
     */
    public function update(SubscriptionRequest $request, string $subscription): JsonResponse
    {
        $found = Subscription::find($subscription);

        if (! $found) {
            return response()->json(['message' => 'Abonnement introuvable.'], 404);
        }

        $found->update($request->validated());

        return response()->json($found);
    }

    /**
     * Delete a plan. Blocked while property owners or recruiters still
     * reference it (409), so subscribers must be reassigned first.
     */
    public function destroy(string $subscription): JsonResponse
    {
        $found = Subscription::withCount(['propertyOwners', 'recruiters'])->find($subscription);

        if (! $found) {
            return response()->json(['message' => 'Abonnement introuvable.'], 404);
        }

        if ($found->property_owners_count > 0 || $found->recruiters_count > 0) {
            return response()->json([
                'message' => 'Ce plan est encore utilisé par des abonnés.',
            ], 409);
        }

        $found->delete();

        return response()->json(['message' => 'Abonnement supprimé.']);
    }
}
