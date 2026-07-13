<?php

namespace App\Models;

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
])]
class Recruiter extends Model
{
    use HasFactory, HasUuids;

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
