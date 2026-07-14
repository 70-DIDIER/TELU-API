<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    /**
     * Paginated list of every payment across the platform (read-only oversight).
     * Filters: ?status=, ?reference_type=, ?payment_method=, ?user_id=.
     */
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with('user:id,full_name,phone')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('reference_type'), fn ($q) => $q->where('reference_type', $request->string('reference_type')))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->string('payment_method')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->string('user_id')))
            ->latest()
            ->paginate(20);

        return response()->json($payments);
    }

    /**
     * Show one payment with its payer.
     */
    public function show(string $payment): JsonResponse
    {
        $found = Payment::query()
            ->with('user:id,full_name,phone,email')
            ->find($payment);

        if (! $found) {
            return response()->json(['message' => 'Paiement introuvable.'], 404);
        }

        return response()->json($found);
    }
}
