<?php

// app/Models/GuestMessageLog.php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GuestMessageLogStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestMessageLog extends Model
{
    protected $table = 'guest_message_logs';

    protected $fillable = [
        'guest_id',
        'template_id',
        'invitation_id',
        'generated_message',
        'generated_url',
        'status',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => GuestMessageLogStatus::class,
            'meta'   => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function guest(): BelongsTo
    {
        return $this->belongsTo(GuestList::class, 'guest_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessageTemplate::class, 'template_id');
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
