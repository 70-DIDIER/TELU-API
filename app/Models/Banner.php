<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Bannière publicitaire du carrousel "Offres du jour" de l'accueil mobile.
 * Contenu entièrement piloté par le back-office (ADMIN-TELU) — l'app ne fait
 * qu'afficher, dans l'ordre, les bannières actives.
 */
#[Fillable(['image_url', 'link_url', 'title', 'position', 'is_active'])]
class Banner extends Model
{
    use HasFactory, HasUuids;

    /**
     * Default attribute values applied on a new instance, before mass
     * assignment — so a create() that omits these still returns them
     * immediately (mirroring the DB column defaults, which Eloquent
     * otherwise never re-reads after an insert without an explicit refresh()).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'position' => 0,
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
