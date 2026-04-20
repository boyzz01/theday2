<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistSubtask extends Model
{
    use HasUuids;

    protected $fillable = [
        'task_id',
        'title',
        'is_completed',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'sort_order'   => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ChecklistTask::class, 'task_id');
    }
}
