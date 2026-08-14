<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'email',
    'password',
    'phone',
    'full_name',
    'profile_photo',
    'user_type',
    'status',
    'deletion_requested_at',
    'deletion_reason',
    'current_latitude',
    'current_longitude',
    'is_verified',
    'phone_verified_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /** Délai de grâce (jours) avant suppression définitive d'un compte. */
    public const DELETION_GRACE_DAYS = 30;

    /** true tant qu'une demande de suppression est en cours (dans le délai). */
    public function hasPendingDeletion(): bool
    {
        return $this->deletion_requested_at !== null;
    }

    /** Date de suppression définitive prévue, ou null si aucune demande. */
    public function deletionPurgeAt(): ?\Illuminate\Support\Carbon
    {
        return $this->deletion_requested_at?->copy()->addDays(self::DELETION_GRACE_DAYS);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'current_latitude' => 'decimal:7',
            'current_longitude' => 'decimal:7',
        ];
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function propertyOwner(): HasOne
    {
        return $this->hasOne(PropertyOwner::class);
    }

    public function recruiter(): HasOne
    {
        return $this->hasOne(Recruiter::class);
    }

    public function jobSeeker(): HasOne
    {
        return $this->hasOne(JobSeeker::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
