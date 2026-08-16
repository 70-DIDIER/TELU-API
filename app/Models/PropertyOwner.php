<?php

namespace App\Models;

use App\Concerns\HasSubscription;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'user_id',
    'owner_type',
    'company_name',
    'subscription_id',
    'subscription_started_at',
    'subscription_expires_at',
    'id_number',
    'id_document_url',
    'ownership_proof_url',
])]
class PropertyOwner extends Model
{
    use HasFactory, HasSubscription, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subscription_started_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    /**
     * Reservations across all of this owner's properties.
     */
    public function reservations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Reservation::class,
            Property::class,
            'owner_id',    // FK on properties -> property_owners
            'property_id', // FK on reservations -> properties
            'id',          // local key on property_owners
            'id'           // local key on properties
        );
    }
}
