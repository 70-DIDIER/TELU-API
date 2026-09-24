<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vendor_id',
    'name',
    'description',
    'price',
    'category',
    'image_url',
    'image_urls',
    'stock',
    'is_available',
    'blocked_at',
])]
class Product extends Model
{
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_available' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    /**
     * Products shown in the public catalogue: enabled by the vendor, not taken
     * down by an admin, in stock, and sold by a vendor that is not suspended.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeListed(Builder $query): void
    {
        $query->where('is_available', true)
            ->whereNull('blocked_at')
            ->where('stock', '>', 0)
            ->whereHas('vendor', fn (Builder $q) => $q->where('is_active', true));
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
