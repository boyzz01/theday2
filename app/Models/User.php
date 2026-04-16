<?php

// app/Models/User.php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'google_id',
        'role',
        'onboarding_completed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'password'                => 'hashed',
            'role'                    => UserRole::class,
        ];
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    // ─── Model Events ─────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            // Soft-delete semua undangan milik user ini
            $user->invitations()->each(fn ($inv) => $inv->delete());
        });
    }

    // ─── Relationships ────────────────────────────────────────────

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function weddingPlan(): HasOne
    {
        return $this->hasOne(WeddingPlan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // ─── Business Logic ───────────────────────────────────────────

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latestOfMany();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription?->plan;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}
