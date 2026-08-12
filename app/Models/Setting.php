<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Backoffice-editable key/value settings (commission rates, delivery pricing,
 * free quotas, PayGate fee rates...). Read via Setting::get(), written by
 * Api\Admin\AdminSettingController. Cached in memory per-request only (see
 * $memo) — an admin change still takes effect on the next request without a
 * redeploy, and each test boots a fresh process so the memo never leaks
 * across tests. Never a persistent cache: a mid-request write must be visible
 * to the next read in the same request, so set() forgets the touched key.
 */
#[Fillable(['key', 'value', 'type', 'group', 'description'])]
class Setting extends Model
{
    use HasFactory, HasUuids;

    /**
     * Per-request typed-value cache, keyed by setting key. A key present with
     * a null value is a genuine "no such row" hit, distinguished from "not yet
     * looked up" by array_key_exists.
     *
     * @var array<string, mixed>
     */
    protected static array $memo = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, static::$memo)) {
            $row = static::query()->where('key', $key)->first();

            static::$memo[$key] = $row === null ? null : match ($row->type) {
                'integer' => (int) $row->value,
                'decimal' => (float) $row->value,
                'boolean' => filter_var($row->value, FILTER_VALIDATE_BOOLEAN),
                default => $row->value,
            };
        }

        return static::$memo[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, string $type = 'string', ?string $group = null, ?string $description = null): self
    {
        // Invalidate the memo so a subsequent get() in the same request re-reads.
        unset(static::$memo[$key]);

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );
    }

    /**
     * Drop the whole per-request memo. Useful in tests that seed settings after
     * a get() has already run, or anywhere a full re-read is required.
     */
    public static function flushCache(): void
    {
        static::$memo = [];
    }
}
