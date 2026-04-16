<?php

// app/Models/InvitationSection.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationSection extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'invitation_id',
        'section_key',
        'step_key',
        'section_type',
        'is_enabled',
        'is_required',
        'is_visible_in_template',
        'completion_status',
        'data_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'             => 'boolean',
            'is_required'            => 'boolean',
            'is_visible_in_template' => 'boolean',
            'data_json'              => 'array',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    // ─── Section defaults used when bootstrapping a new invitation ────

    public static function defaults(): array
    {
        return [
            // Informasi
            ['section_key' => 'cover',               'step_key' => 'informasi', 'section_type' => 'cover',               'is_required' => true,  'is_enabled' => true,  'sort_order' => 0],
            ['section_key' => 'konten_utama',        'step_key' => 'informasi', 'section_type' => 'konten_utama',        'is_required' => true,  'is_enabled' => true,  'sort_order' => 1],
            ['section_key' => 'couple',              'step_key' => 'informasi', 'section_type' => 'couple',              'is_required' => true,  'is_enabled' => true,  'sort_order' => 2],
            ['section_key' => 'quote',               'step_key' => 'informasi', 'section_type' => 'quote',               'is_required' => false, 'is_enabled' => false, 'sort_order' => 3],
            // Acara
            ['section_key' => 'events',              'step_key' => 'acara',     'section_type' => 'events',              'is_required' => true,  'is_enabled' => true,  'sort_order' => 0],
            ['section_key' => 'countdown',           'step_key' => 'acara',     'section_type' => 'countdown',           'is_required' => false, 'is_enabled' => true,  'sort_order' => 1],
            ['section_key' => 'live_streaming',      'step_key' => 'acara',     'section_type' => 'live_streaming',      'is_required' => false, 'is_enabled' => false, 'sort_order' => 2],
            ['section_key' => 'additional_info',     'step_key' => 'acara',     'section_type' => 'additional_info',     'is_required' => false, 'is_enabled' => false, 'sort_order' => 3],
            // Media
            ['section_key' => 'gallery',             'step_key' => 'media',     'section_type' => 'gallery',             'is_required' => false, 'is_enabled' => false, 'sort_order' => 0],
            ['section_key' => 'video',               'step_key' => 'media',     'section_type' => 'video',               'is_required' => false, 'is_enabled' => false, 'sort_order' => 1],
            ['section_key' => 'love_story',          'step_key' => 'media',     'section_type' => 'love_story',          'is_required' => false, 'is_enabled' => false, 'sort_order' => 2],
            // Interaksi
            ['section_key' => 'rsvp',                'step_key' => 'interaksi', 'section_type' => 'rsvp',                'is_required' => false, 'is_enabled' => true,  'sort_order' => 0],
            ['section_key' => 'wishes',              'step_key' => 'interaksi', 'section_type' => 'wishes',              'is_required' => false, 'is_enabled' => true,  'sort_order' => 1],
            ['section_key' => 'gift',                'step_key' => 'interaksi', 'section_type' => 'gift',                'is_required' => false, 'is_enabled' => false, 'sort_order' => 2],
            // Tampilan
            ['section_key' => 'music',               'step_key' => 'tampilan',  'section_type' => 'music',               'is_required' => false, 'is_enabled' => false, 'sort_order' => 0],
            ['section_key' => 'theme_settings',      'step_key' => 'tampilan',  'section_type' => 'theme_settings',      'is_required' => true,  'is_enabled' => true,  'sort_order' => 1],
            // Publikasi
            ['section_key' => 'slug_settings',       'step_key' => 'publikasi', 'section_type' => 'slug_settings',       'is_required' => true,  'is_enabled' => true,  'sort_order' => 0],
            ['section_key' => 'password_protection', 'step_key' => 'publikasi', 'section_type' => 'password_protection', 'is_required' => false, 'is_enabled' => false, 'sort_order' => 1],
            ['section_key' => 'preview_and_publish', 'step_key' => 'publikasi', 'section_type' => 'preview_and_publish', 'is_required' => true,  'is_enabled' => true,  'sort_order' => 2],
        ];
    }

    // Bootstrap all default sections for a new invitation
    public static function initializeForInvitation(string $invitationId): void
    {
        $now = now();
        $rows = array_map(fn ($s) => array_merge($s, [
            'id'                => (string) \Illuminate\Support\Str::uuid(),
            'invitation_id'     => $invitationId,
            'completion_status' => 'empty',
            'data_json'         => null,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]), static::defaults());

        // Update structural config only — do NOT reset is_enabled so user toggles are preserved.
        static::upsert($rows, ['invitation_id', 'section_key'], ['step_key', 'section_type', 'is_required', 'sort_order']);
    }
}
