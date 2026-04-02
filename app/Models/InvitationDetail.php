<?php

// app/Models/InvitationDetail.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationDetail extends Model
{
    use HasUuids;

    protected $fillable = [
        'invitation_id',
        'groom_name',
        'bride_name',
        'groom_photo_url',
        'bride_photo_url',
        'groom_parent_names',
        'bride_parent_names',
        'birthday_person_name',
        'birthday_photo_url',
        'birthday_age',
        'opening_text',
        'closing_text',
        'cover_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'birthday_age' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }
}
