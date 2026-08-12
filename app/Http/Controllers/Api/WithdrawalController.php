<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWithdrawalRequest;
use App\Models\Driver;
use App\Models\Vendor;
use App\Models\WithdrawalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawalController extends Controller
{
    /**
     * List the authenticated vendor/driver's own withdrawal requests.
     */
    public function index(Request $request): JsonResponse
    {
        $walletable = $this->walletableOrFail($request);

        if ($walletable instanceof JsonResponse) {
            return $walletable;
        }

        $wallet = $walletable->wallet;

        $withdrawals = $wallet
            ? $wallet->withdrawalRequests()->latest()->paginate(20)
            : collect();

        return response()->json($withdrawals);
    }

    /**
     * Request a payout. The wallet is debited immediately (funds reserved);
     * an admin later sends the mobile money transfer manually and marks the
     * request `paid` (no automated PayGate disbursement API is available —
     * see App\Services\PayGate).
     */
    public function store(StoreWithdrawalRequest $request): JsonResponse
    {
        $walletable = $this->walletableOrFail($request);

        if ($walletable instanceof JsonResponse) {
            return $walletable;
        }

        $data = $request->validated();
        $wallet = $walletable->walletOrCreate();

        // Débit et création de la demande dans une même transaction : si l'un
        // échoue, l'autre est annulé — jamais de fonds réservés sans demande.
        $withdrawal = DB::transaction(function () use ($wallet, $data) {
            $transaction = $wallet->debit(
                (float) $data['amount'],
                'withdrawal',
                null,
                'Demande de retrait'
            );

            if (! $transaction) {
                return null;
            }

            return WithdrawalRequest::create([
                'wallet_id' => $wallet->id,
                'amount' => $data['amount'],
                'phone_number' => $data['phone_number'],
                'status' => 'pending',
            ]);
        });

        if (! $withdrawal) {
            return response()->json(['message' => 'Solde insuffisant.'], 422);
        }

        return response()->json($withdrawal, 201);
    }

    /**
     * Resolve the authenticated user's vendor or driver profile (the only two
     * wallet holders), or a 403 JsonResponse.
     */
    private function walletableOrFail(Request $request): Vendor|Driver|JsonResponse
    {
        $user = $request->user();

        if ($user->vendor) {
            return $user->vendor;
        }

        if ($user->driver) {
            return $user->driver;
        }

        return response()->json([
            'message' => 'Vous devez d\'abord créer votre profil vendeur ou livreur.',
        ], 403);
    }
}
