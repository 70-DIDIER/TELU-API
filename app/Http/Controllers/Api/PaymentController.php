<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Subscription;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * List the authenticated user's payments.
     * Optional filters: ?status=, ?reference_type=.
     */
    public function index(Request $request): JsonResponse
    {
        $payments = $request->user()
            ->payments()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('reference_type'), fn ($q) => $q->where('reference_type', $request->string('reference_type')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($payments);
    }

    /**
     * Show one of the authenticated user's payments.
     */
    public function show(Request $request, string $payment): JsonResponse
    {
        $found = $request->user()->payments()->find($payment);

        if (! $found) {
            return response()->json(['message' => 'Paiement introuvable.'], 404);
        }

        return response()->json($found);
    }

    /**
     * Initiate a (simulated) payment for an order, reservation or subscription.
     * The amount is resolved server-side from the referenced entity.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $amount = $this->resolveAmount($data['reference_type'], $data['reference_id'], $user->id);

        if ($amount instanceof JsonResponse) {
            return $amount; // 404 not found / 403 not owned.
        }

        $alreadyPaid = Payment::query()
            ->where('reference_type', $data['reference_type'])
            ->where('reference_id', $data['reference_id'])
            ->where('status', 'success')
            ->exists();

        if ($alreadyPaid) {
            return response()->json(['message' => 'Cet élément a déjà été payé.'], 409);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => $data['payment_method'],
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'status' => 'pending',
            'transaction_id' => 'SIM-'.strtoupper(Str::random(12)),
        ]);

        return response()->json($payment, 201);
    }

    /**
     * Simulate the payment gateway callback for a pending payment.
     * Body: outcome=success (default) | failed.
     */
    public function confirm(ConfirmPaymentRequest $request, string $payment): JsonResponse
    {
        $found = $request->user()->payments()->find($payment);

        if (! $found) {
            return response()->json(['message' => 'Paiement introuvable.'], 404);
        }

        if ($found->status !== 'pending') {
            return response()->json([
                'message' => 'Seul un paiement en attente peut être confirmé.',
            ], 422);
        }

        $outcome = $request->validated()['outcome'] ?? 'success';

        $found->update(['status' => $outcome]);

        Notifier::send(
            $found->user_id,
            'payment',
            $outcome === 'success'
                ? "Paiement de {$found->amount} confirmé."
                : "Votre paiement de {$found->amount} a échoué."
        );

        return response()->json($found->fresh());
    }

    /**
     * Resolve the payable amount for a reference, enforcing ownership.
     * Returns the amount, or a JsonResponse error (404/403).
     */
    private function resolveAmount(string $type, string $referenceId, string $userId): float|JsonResponse
    {
        switch ($type) {
            case 'order':
                $order = Order::find($referenceId);
                if (! $order) {
                    return response()->json(['message' => 'Commande introuvable.'], 404);
                }
                if ($order->customer_id !== $userId) {
                    return response()->json(['message' => 'Cette commande ne vous appartient pas.'], 403);
                }

                return (float) $order->total_amount;

            case 'reservation':
                $reservation = Reservation::find($referenceId);
                if (! $reservation) {
                    return response()->json(['message' => 'Réservation introuvable.'], 404);
                }
                if ($reservation->customer_id !== $userId) {
                    return response()->json(['message' => 'Cette réservation ne vous appartient pas.'], 403);
                }

                return (float) $reservation->total_price;

            case 'subscription':
                $subscription = Subscription::find($referenceId);
                if (! $subscription) {
                    return response()->json(['message' => 'Abonnement introuvable.'], 404);
                }

                return (float) $subscription->price;
        }

        return response()->json(['message' => 'Type de référence invalide.'], 404);
    }
}
