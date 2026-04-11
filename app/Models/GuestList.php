<?php

// app/Models/GuestList.php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GuestRsvpStatus;
use App\Enums\GuestSendStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestList extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'guest_lists';

    protected $fillable = [
        'user_id',
        'invitation_id',
        'name',
        'guest_slug',
        'phone_number',
        'normalized_phone',
        'category',
        'greeting',
        'note',
        'sent_count',
        'send_status',
        'rsvp_status',
        'first_opened_at',
        'last_opened_at',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'send_status'     => GuestSendStatus::class,
            'rsvp_status'     => GuestRsvpStatus::class,
            'first_opened_at' => 'datetime',
            'last_opened_at'  => 'datetime',
            'last_sent_at'    => 'datetime',
            'sent_count'      => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(GuestMessageLog::class, 'guest_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone_number', 'like', "%{$search}%")
              ->orWhere('normalized_phone', 'like', "%{$search}%");
        });
    }

    public function scopeFilterSendStatus(Builder $query, string $status): Builder
    {
        return $query->where('send_status', $status);
    }

    public function scopeFilterRsvpStatus(Builder $query, string $status): Builder
    {
        return $query->where('rsvp_status', $status);
    }

    public function scopeFilterCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeFilterInvitation(Builder $query, string $invitationId): Builder
    {
        return $query->where('invitation_id', $invitationId);
    }

    public function scopeSortBy(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'name_asc'  => $query->orderBy('name'),
            'not_sent'  => $query->orderByRaw("
                CASE send_status
                    WHEN 'not_sent' THEN 0
                    WHEN 'sent'     THEN 1
                    WHEN 'opened'   THEN 2
                END
            ")->orderBy('name'),
            'not_rsvp'  => $query->orderByRaw("
                CASE rsvp_status
                    WHEN 'pending'       THEN 0
                    WHEN 'attending'     THEN 1
                    WHEN 'not_attending' THEN 2
                END
            ")->orderBy('name'),
            default     => $query->latest(),
        };
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isOpened(): bool
    {
        return $this->send_status === GuestSendStatus::Opened;
    }

    public function isSent(): bool
    {
        return in_array($this->send_status, [GuestSendStatus::Sent, GuestSendStatus::Opened]);
    }
}
