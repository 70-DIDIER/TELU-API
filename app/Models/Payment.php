<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'amount',
    'payment_method',
    'reference_type',
    'reference_id',
    'status',
    'transaction_id',
    'identifier',
    'payment_reference',
    'phone_number',
    'paid_at',
])]
class Payment extends Model
{
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve the paid entity (order|reservation|subscription) referenced by
     * reference_type / reference_id.
     */
    public function reference(): ?Model
    {
        return match ($this->reference_type) {
            'order' => Order::find($this->reference_id),
            'reservation' => Reservation::find($this->reference_id),
            'subscription' => Subscription::find($this->reference_id),
            default => null,
        };
    }
}
