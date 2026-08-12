<?php

namespace App\Concerns;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Shared by Vendor and Driver: a single polymorphic Wallet accumulating their
 * net earnings (commission already deducted) from orders/deliveries.
 */
trait HasWallet
{
    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'walletable');
    }

    /**
     * Fetch the wallet, creating it on first use (lazy — most profiles never
     * need one until their first settled order/delivery).
     */
    public function walletOrCreate(): Wallet
    {
        return $this->wallet ?? $this->wallet()->create(['balance' => 0]);
    }

    public function creditWallet(float $amount, string $referenceType, ?string $referenceId, string $description): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->walletOrCreate()->credit($amount, $referenceType, $referenceId, $description);
    }
}
