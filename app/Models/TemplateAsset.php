<?php

// app/Models/TemplateAsset.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateAsset extends Model
{
    use HasUuids;

    protected $fillable = [
        'template_id',
        'file_url',
        'type',
        'label',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
