<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionVariant extends Model
{
    use HasUuids;

    protected $fillable = [
        'section_type',
        'code',
        'name',
        'status',
        'schema_json',
        'ui_meta_json',
        'render_component',
        'editor_component',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'schema_json'   => 'array',
            'ui_meta_json'  => 'array',
            'version'       => 'integer',
        ];
    }

    public function invitationSections(): HasMany
    {
        return $this->hasMany(InvitationSection::class, 'variant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
