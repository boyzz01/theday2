<?php

// app/Models/InvitationEvent.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'invitation_id',
        'event_name',
        'event_date',
        'start_time',
        'end_time',
        'venue_name',
        'venue_address',
        'latitude',
        'longitude',
        'maps_url',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'latitude'   => 'decimal:8',
            'longitude'  => 'decimal:8',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
