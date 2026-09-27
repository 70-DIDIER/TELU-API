<?php

namespace App\Models;

use App\Concerns\HasWallet;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'vehicle_type',
    'license_number',
    'coverage_zone',
    'is_available',
    'current_latitude',
    'current_longitude',
    'id_document_url',
    'id_document_type',
    'vehicle_photo_url',
    'verification_status',
    'verification_notes',
    'verified_at',
])]
class Driver extends Model
{
    use HasFactory, HasUuids, HasWallet;

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
            'is_available' => 'boolean',
            'current_latitude' => 'decimal:7',
            'current_longitude' => 'decimal:7',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
