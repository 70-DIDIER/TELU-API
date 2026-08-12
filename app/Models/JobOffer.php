<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'recruiter_id',
    'title',
    'description',
    'location',
    'latitude',
    'longitude',
    'daily_rate',
    'required_skills',
    'start_date',
    'duration',
    'people_needed',
    'is_active',
])]
class JobOffer extends Model
{
    use HasFactory, HasUuids;

    /**
     * Computed from the recruiter's subscription status — see getIsFeaturedAttribute().
     * Requires the `recruiter` relation to be eager-loaded with `subscription_expires_at`.
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
            'daily_rate' => 'decimal:2',
            'start_date' => 'date',
            'people_needed' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(Recruiter::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Offers from a recruiter with an active subscription are boosted/featured
     * on the public job board (JobOfferController::index() sorts on this).
     */
    public function getIsFeaturedAttribute(): bool
    {
        return $this->relationLoaded('recruiter') && $this->recruiter
            ? $this->recruiter->hasActiveSubscription()
            : false;
    }
}
