<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'push_token_id',
    'ticket_id',
    'status',
    'error_code',
    'error_message',
    'checked_at',
])]
class PushReceipt extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function pushToken(): BelongsTo
    {
        return $this->belongsTo(PushToken::class);
    }
}
