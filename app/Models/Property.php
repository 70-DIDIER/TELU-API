<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_id',
    'title',
    'description',
    'property_type',
    'address',
    'latitude',
    'longitude',
    'price',
    'price_unit',
    'bedrooms',
    'image_urls',
    'is_available',
])]
class Property extends Model
{
    use HasFactory, HasUuids;

    /**
     * Computed from the owner's subscription status — see getIsFeaturedAttribute().
     * Requires the `owner` relation to be eager-loaded with `subscription_expires_at`.
     *
     * @var list<string>
     */
    protected $appends = ['is_featured'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'price' => 'decimal:2',
            'bedrooms' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(PropertyOwner::class, 'owner_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Listings from an owner with an active subscription are boosted/featured
     * in the public catalogue (PropertyController::index() sorts on this).
     */
    public function getIsFeaturedAttribute(): bool
    {
        return $this->relationLoaded('owner') && $this->owner
            ? $this->owner->hasActiveSubscription()
            : false;
    }
}
