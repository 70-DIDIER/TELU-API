<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayGateCallbackRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Subscription;
use App\Services\Notifier;
use App\Services\PayGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(private readonly PayGate $payGate) {}

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
     * Initiate a mobile money payment (Flooz / TMoney) through PayGate Global
     * for an order, a reservation or a subscription. The amount is resolved
     * server-side from the referenced entity, never from the body.
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

        $identifier = 'TELU-'.strtoupper(Str::random(12));

        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => $data['payment_method'],
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'status' => 'pending',
            'identifier' => $identifier,
            'phone_number' => $data['phone_number'],
        ]);

        $result = $this->payGate->requestPayment(
            phoneNumber: $data['phone_number'],
            amount: (float) $amount,
            identifier: $identifier,
            paymentMethod: $data['payment_method'],
            description: $this->describe($data['reference_type'], $data['reference_id']),
        );

        if (! $result['ok']) {
            $payment->update(['status' => 'failed']);

            return response()->json([
                'message' => $result['message'] ?? 'Le paiement n\'a pas pu être initié.',
                'payment' => $payment->fresh(),
            ], 502);
        }

        $payment->update(['transaction_id' => $result['tx_reference'] ?? null]);

        return response()->json([
            'message' => 'Paiement initié. Validez la transaction sur votre téléphone (code secret mobile money).',
            'payment' => $payment->fresh(),
        ], 201);
    }

    /**
     * Poll PayGate for the current state of one of the caller's payments.
     * Used by the client app while the customer validates on their handset.
     */
    public function check(Request $request, string $payment): JsonResponse
    {
        $found = $request->user()->payments()->find($payment);

        if (! $found) {
            return response()->json(['message' => 'Paiement introuvable.'], 404);
        }

        if ($found->status !== 'pending') {
            return response()->json($found);
        }

        $gateway = $this->fetchGatewayStatus($found);

        if (! ($gateway['ok'] ?? false)) {
            return response()->json([
                'message' => $gateway['message'] ?? 'Impossible de vérifier le paiement pour le moment.',
                'payment' => $found,
            ], 502);
        }

        $this->applyGatewayStatus($found, $gateway);

        return response()->json($found->fresh());
    }

    /**
     * PayGate Global confirmation webhook (public, unauthenticated).
     * The posted body is unsigned, so it only acts as a trigger: the real
     * state is read back from PayGate before anything is written.
     */
    public function callback(PayGateCallbackRequest $request): JsonResponse
    {
        $identifier = $request->validated()['identifier'];
        $payment = Payment::where('identifier', $identifier)->first();

        if (! $payment) {
            Log::warning('PayGate: callback reçu pour un identifiant inconnu', ['identifier' => $identifier]);

            return response()->json(['received' => true]);
        }

        if ($payment->status === 'pending') {
            $gateway = $this->fetchGatewayStatus($payment);

            if ($gateway['ok'] ?? false) {
                $this->applyGatewayStatus($payment, $gateway);
            }
        }

        return response()->json(['received' => true]);
    }

    /**
     * Read the authoritative status from PayGate, by tx_reference when known,
     * otherwise by our own identifier.
     *
     * @return array<string, mixed>
     */
    private function fetchGatewayStatus(Payment $payment): array
    {
        return $payment->transaction_id
            ? $this->payGate->statusByReference($payment->transaction_id)
            : $this->payGate->statusByIdentifier((string) $payment->identifier);
    }

    /**
     * Persist a resolved gateway status on a pending payment and notify the user.
     *
     * @param  array<string, mixed>  $gateway
     */
    private function applyGatewayStatus(Payment $payment, array $gateway): void
    {
        $status = $gateway['payment_status'] ?? null;

        if ($status === null || $status === 'pending' || $payment->status !== 'pending') {
            return;
        }

        $payment->update([
            'status' => $status,
            'transaction_id' => $payment->transaction_id ?: ($gateway['tx_reference'] ?? null),
            'payment_reference' => $gateway['payment_reference'] ?? null,
            'paid_at' => $status === 'success' ? now() : null,
        ]);

        Notifier::send(
            $payment->user_id,
            'payment',
            $status === 'success'
                ? "Paiement de {$payment->amount} FCFA confirmé."
                : "Votre paiement de {$payment->amount} FCFA n'a pas abouti."
        );
    }

    /**
     * Human-readable label sent to PayGate (shown on the merchant dashboard).
     */
    private function describe(string $type, string $referenceId): string
    {
        $label = match ($type) {
            'order' => 'Commande',
            'reservation' => 'Réservation',
            'subscription' => 'Abonnement',
            default => 'Paiement',
        };

        return "TELU BAOBAB — {$label} ".substr($referenceId, 0, 8);
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
