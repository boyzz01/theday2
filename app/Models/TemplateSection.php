<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateSection extends Model
{
    use HasUuids;

    protected $fillable = [
        'template_id',
        'section_key',
        'section_type',
        'label',
        'default_variant_id',
        'is_required',
        'is_enabled_by_default',
        'is_removable',
        'sort_order',
        'supports_multiple_items',
        'supports_reordering',
        'default_data_json',
        'default_style_json',
        'rules_json',
        'visibility_conditions_json',
    ];

    protected function casts(): array
    {
        return [
            'is_required'                 => 'boolean',
            'is_enabled_by_default'       => 'boolean',
            'is_removable'                => 'boolean',
            'supports_multiple_items'     => 'boolean',
            'supports_reordering'         => 'boolean',
            'default_data_json'           => 'array',
            'default_style_json'          => 'array',
            'rules_json'                  => 'array',
            'visibility_conditions_json'  => 'array',
            'sort_order'                  => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function defaultVariant(): BelongsTo
    {
        return $this->belongsTo(SectionVariant::class, 'default_variant_id');
    }

    public function invitationSections(): HasMany
    {
        return $this->hasMany(InvitationSection::class);
    }
}
