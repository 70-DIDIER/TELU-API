<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'phone',
    'purpose',
    'code_hash',
    'attempts',
    'expires_at',
    'verified_at',
    'consumed_at',
    'verification_token',
    'resource_id',
    'ip',
])]
#[Hidden(['code_hash'])]
class OtpCode extends Model
{
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Le code est-il encore utilisable (ni expiré, ni déjà validé/consommé) ?
     */
    public function isPending(): bool
    {
        return $this->verified_at === null
            && $this->consumed_at === null
            && $this->expires_at->isFuture();
    }
}
