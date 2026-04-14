<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationSection extends Model
{
    use HasUuids;

    protected $fillable = [
        'invitation_id',
        'template_section_id',
        'section_key',
        'section_type',
        'label',
        'variant_id',
        'is_enabled',
        'is_required',
        'is_hidden',
        'sort_order',
        'completion_status',
        'validation_errors_json',
        'data_json',
        'style_json',
        'meta_json',
        'last_validated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'             => 'boolean',
            'is_required'            => 'boolean',
            'is_hidden'              => 'boolean',
            'sort_order'             => 'integer',
            'validation_errors_json' => 'array',
            'data_json'              => 'array',
            'style_json'             => 'array',
            'meta_json'              => 'array',
            'last_validated_at'      => 'datetime',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function templateSection(): BelongsTo
    {
        return $this->belongsTo(TemplateSection::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(SectionVariant::class, 'variant_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    public function isComplete(): bool
    {
        return $this->completion_status === 'complete';
    }

    public function hasWarning(): bool
    {
        return in_array($this->completion_status, ['warning', 'error'], true);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
