<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'rater_id',
    'target_type',
    'target_id',
    'score',
    'comment',
])]
class Rating extends Model
{
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    /**
     * Resolve the rated profile (vendor|driver|property_owner|recruiter|job_seeker)
     * referenced by target_type / target_id.
     */
    public function target(): ?Model
    {
        return match ($this->target_type) {
            'vendor' => Vendor::find($this->target_id),
            'driver' => Driver::find($this->target_id),
            'property_owner' => PropertyOwner::find($this->target_id),
            'recruiter' => Recruiter::find($this->target_id),
            'job_seeker' => JobSeeker::find($this->target_id),
            default => null,
        };
    }
}
