<?php

declare(strict_types=1);

namespace App\Actions\BudgetPlanner;

use App\Enums\BudgetPaymentStatus;
use App\Models\WeddingBudget;
use App\Support\Formatters\RupiahFormatter;
use Illuminate\Support\Collection;

final class GetBudgetItemsTableAction
{
    public function execute(WeddingBudget $budget, array $filters = []): array
    {
        $query = $budget->activeItems()
            ->with('category')
            ->whereNull('deleted_at');

        // Search
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(fn ($b) => $b
                ->where('title', 'like', "%{$q}%")
                ->orWhere('vendor_name', 'like', "%{$q}%")
                ->orWhere('notes', 'like', "%{$q}%")
            );
        }

        // Filter by category
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter by payment status
        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filter by actual amount existence
        if (isset($filters['has_actual'])) {
            if ($filters['has_actual']) {
                $query->whereNotNull('actual_amount');
            } else {
                $query->whereNull('actual_amount');
            }
        }

        // Sort
        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'amount_desc'    => $query->orderByDesc('planned_amount'),
            'amount_asc'     => $query->orderBy('planned_amount'),
            'date_desc'      => $query->orderByDesc('payment_date'),
            'category'       => $query->orderBy('category_id'),
            'payment_status' => $query->orderBy('payment_status'),
            default          => $query->orderByDesc('created_at'),
        };

        $items = $query->get();

        return $items->map(fn ($item) => $this->itemResource($item))->values()->toArray();
    }

    public function itemResource(mixed $item): array
    {
        return [
            'id'                   => $item->id,
            'title'                => $item->title,
            'vendor_name'          => $item->vendor_name,
            'notes'                => $item->notes,
            'category'             => $item->category ? [
                'id'   => $item->category->id,
                'name' => $item->category->name,
            ] : null,
            'planned_amount'       => $item->planned_amount,
            'actual_amount'        => $item->actual_amount,
            'payment_status'       => $item->payment_status->value,
            'payment_status_label' => $item->payment_status->label(),
            'payment_status_badge' => $item->payment_status->badgeClass(),
            'payment_date'         => $item->payment_date?->format('Y-m-d'),
            'payment_date_label'   => $item->payment_date?->translatedFormat('d M Y'),
            'is_archived'          => $item->is_archived,
            'formatted'            => [
                'planned_amount'    => RupiahFormatter::formatOrZero($item->planned_amount),
                'actual_amount'     => $item->actual_amount !== null
                    ? RupiahFormatter::format($item->actual_amount)
                    : null,
                'actual_amount_display' => $item->actual_amount !== null
                    ? RupiahFormatter::format($item->actual_amount)
                    : 'Belum dicatat',
            ],
        ];
    }
}
