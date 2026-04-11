<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BudgetPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WeddingBudgetItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'budget_id',
        'category_id',
        'invitation_id',
        'title',
        'vendor_name',
        'notes',
        'planned_amount',
        'actual_amount',
        'payment_status',
        'payment_date',
        'is_archived',
    ];

    protected $casts = [
        'planned_amount' => 'integer',
        'actual_amount'  => 'integer',
        'payment_date'   => 'date',
        'is_archived'    => 'boolean',
        'payment_status' => BudgetPaymentStatus::class,
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(WeddingBudget::class, 'budget_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WeddingBudgetCategory::class, 'category_id');
    }
}
