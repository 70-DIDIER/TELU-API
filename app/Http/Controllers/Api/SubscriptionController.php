<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Browse the available subscription plans, so a property owner/recruiter
     * can pick one before paying via POST /api/payments. Filter: ?subscriber_type=.
     */
    public function index(Request $request): JsonResponse
    {
        $plans = Subscription::query()
            ->when($request->filled('subscriber_type'), fn ($q) => $q->where('subscriber_type', $request->string('subscriber_type')))
            ->orderBy('price')
            ->get();

        return response()->json($plans);
    }
}
