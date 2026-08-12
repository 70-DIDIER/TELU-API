<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'price',
    'duration_days',
    'features',
    'subscriber_type',
])]
class Subscription extends Model
{
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
        ];
    }

    public function propertyOwners(): HasMany
    {
        return $this->hasMany(PropertyOwner::class);
    }

    public function recruiters(): HasMany
    {
        return $this->hasMany(Recruiter::class);
    }
}
