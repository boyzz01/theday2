<?php

// app/Models/GuestMessage.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestMessage extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'invitation_id',
        'name',
        'message',
        'is_anonymous',
        'is_approved',
        'is_hidden',
        'is_pinned',
        'pinned_at',
        'hidden_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'is_approved'  => 'boolean',
            'is_hidden'    => 'boolean',
            'is_pinned'    => 'boolean',
            'pinned_at'    => 'datetime',
            'hidden_at'    => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────

    /** Visible on public page: not hidden, not soft-deleted */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /** Pinned messages */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /** Hidden messages */
    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('is_hidden', true);
    }

    /** Legacy: kept for backwards compat */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_approved', false);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /** Display name: "Tamu Anonim" if anonymous */
    public function displayName(): string
    {
        return $this->is_anonymous ? 'Tamu Anonim' : $this->name;
    }
}
