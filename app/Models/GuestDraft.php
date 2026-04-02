<?php

// app/Models/GuestDraft.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestDraft extends Model
{
    use HasUuids;

    protected $fillable = [
        'guest_session_id',
        'template_id',
        'data',
        'step',
        'expires_at',
    ];

    protected $casts = [
        'data'       => 'array',
        'expires_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $draft): void {
            if (empty($draft->expires_at)) {
                $draft->expires_at = now()->addDays(7);
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeBySession(Builder $query, string $sessionId): Builder
    {
        return $query->where('guest_session_id', $sessionId);
    }
}
