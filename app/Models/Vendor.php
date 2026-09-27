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
    'shop_name',
    'logo_url',
    'description',
    'address',
    'latitude',
    'longitude',
    'is_active',
    'id_number',
    'id_document_url',
    'id_document_type',
    'rccm_number',
    'rccm_document_url',
    'verification_status',
    'verification_notes',
    'verified_at',
])]
class Vendor extends Model
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
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
