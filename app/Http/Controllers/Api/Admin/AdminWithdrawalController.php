<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWithdrawalController extends Controller
{
    /**
     * Paginated list of every payout request. Filter: ?status=.
     */
    public function index(Request $request): JsonResponse
    {
        $withdrawals = WithdrawalRequest::query()
            ->with('wallet.walletable')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20);

        return response()->json($withdrawals);
    }

    /**
     * Mark a pending request as paid, once the admin has sent the mobile
     * money transfer manually (no automated PayGate disbursement API exists).
     */
    public function markPaid(string $withdrawal): JsonResponse
    {
        $found = WithdrawalRequest::with('wallet.walletable')->find($withdrawal);

        if (! $found) {
            return response()->json(['message' => 'Demande de retrait introuvable.'], 404);
        }

        if ($found->status !== 'pending') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        $found->update([
            'status' => 'paid',
            'processed_at' => now(),
            'processed_by' => request()->user()->id,
        ]);

        $this->notifyWalletable($found, 'wallet', "Votre retrait de {$found->amount} FCFA a été envoyé.");

        return response()->json($found);
    }

    /**
     * Reject a pending request and credit the wallet back (the funds were
     * reserved/debited at request time).
     */
    public function reject(string $withdrawal): JsonResponse
    {
        $found = WithdrawalRequest::with('wallet.walletable')->find($withdrawal);

        if (! $found) {
            return response()->json(['message' => 'Demande de retrait introuvable.'], 404);
        }

        if ($found->status !== 'pending') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 422);
        }

        $found->wallet->credit(
            (float) $found->amount,
            'withdrawal',
            $found->id,
            'Retrait rejeté — remboursement'
        );

        $found->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => request()->user()->id,
        ]);

        $this->notifyWalletable($found, 'wallet', "Votre demande de retrait de {$found->amount} FCFA a été rejetée et le montant recrédité sur votre portefeuille.");

        return response()->json($found);
    }

    private function notifyWalletable(WithdrawalRequest $withdrawal, string $type, string $message): void
    {
        $walletable = $withdrawal->wallet?->walletable;

        if ($walletable) {
            Notifier::send($walletable->user_id, $type, $message);
        }
    }
}
