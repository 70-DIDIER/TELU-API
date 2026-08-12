<?php

namespace App\Concerns;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared by PropertyOwner and Recruiter — the only two subscriber types
 * (vendors/drivers are commission-based, see App\Concerns\HasWallet): a
 * nullable link to a Subscription plan plus the start/expiry window activated
 * by PaymentController::activateSubscription() on a successful subscription
 * payment.
 */
trait HasSubscription
{
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_id !== null
            && $this->subscription_expires_at !== null
            && $this->subscription_expires_at->isFuture();
    }
}
