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
    'company_name',
    'industry',
    'subscription_id',
    'subscription_started_at',
    'subscription_expires_at',
    'id_number',
    'id_document_url',
    'id_document_type',
    'rccm_number',
    'company_document_url',
    'verification_status',
    'verification_notes',
    'verified_at',
])]
class Recruiter extends Model
{
    use HasFactory, HasSubscription, HasUuids;

    /**
     * Mirrors the DB default so a create() that omits it still returns
     * 'pending' immediately (Eloquent never re-reads DB column defaults
     * after an insert without an explicit refresh()).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'verification_status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subscription_started_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobOffers(): HasMany
    {
        return $this->hasMany(JobOffer::class);
    }

    /**
     * Applications across all of this recruiter's job offers.
     */
    public function applications(): HasManyThrough
    {
        return $this->hasManyThrough(
            JobApplication::class,
            JobOffer::class,
            'recruiter_id', // FK on job_offers -> recruiters
            'job_offer_id', // FK on job_applications -> job_offers
            'id',           // local key on recruiters
            'id'            // local key on job_offers
        );
    }
}
