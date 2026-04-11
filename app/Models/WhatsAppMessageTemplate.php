<?php

// app/Models/WhatsAppMessageTemplate.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppMessageTemplate extends Model
{
    protected $table = 'whatsapp_message_templates';

    protected $fillable = [
        'user_id',
        'invitation_id',
        'name',
        'content',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
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
        return $this->hasMany(GuestMessageLog::class, 'template_id');
    }

    // ─── Defaults ─────────────────────────────────────────────────

    public static function defaultContent(): string
    {
        return "Halo {{guest_name}},\n\nDengan penuh kebahagiaan, kami mengundang Anda untuk hadir di hari spesial kami.\n\nBuka undangannya di sini:\n{{invitation_url}}\n\nTerima kasih atas doa dan kehadirannya.";
    }
}
