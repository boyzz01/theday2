<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BudgetCategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeddingBudgetCategory extends Model
{
    protected $fillable = [
        'budget_id',
        'name',
        'slug',
        'type',
        'sort_order',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'type'        => BudgetCategoryType::class,
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(WeddingBudget::class, 'budget_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WeddingBudgetItem::class, 'category_id');
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(WeddingBudgetItem::class, 'category_id')
            ->where('is_archived', false);
    }
}
