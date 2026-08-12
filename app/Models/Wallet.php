<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

#[Fillable(['walletable_type', 'walletable_id', 'balance'])]
class Wallet extends Model
{
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    public function walletable(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /**
     * Credit the wallet and record the ledger line, row-locked to stay
     * consistent under concurrent settlements.
     */
    public function credit(float $amount, string $referenceType, ?string $referenceId, string $description): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $referenceType, $referenceId, $description) {
            $wallet = static::query()->lockForUpdate()->findOrFail($this->id);
            $balanceAfter = round((float) $wallet->balance + $amount, 2);
            $wallet->update(['balance' => $balanceAfter]);

            return $wallet->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);
        });
    }

    /**
     * Debit the wallet (e.g. a withdrawal request reserving funds). Returns
     * null without doing anything if the balance is insufficient — the
     * caller is expected to check first and report a clean 422.
     */
    public function debit(float $amount, string $referenceType, ?string $referenceId, string $description): ?WalletTransaction
    {
        return DB::transaction(function () use ($amount, $referenceType, $referenceId, $description) {
            $wallet = static::query()->lockForUpdate()->findOrFail($this->id);

            if ((float) $wallet->balance < $amount) {
                return null;
            }

            $balanceAfter = round((float) $wallet->balance - $amount, 2);
            $wallet->update(['balance' => $balanceAfter]);

            return $wallet->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);
        });
    }
}
