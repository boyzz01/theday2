<?php

declare(strict_types=1);

namespace App\Actions\BudgetPlanner;

use App\Models\WeddingBudget;
use App\Support\Formatters\RupiahFormatter;

final class BuildCategoryBreakdownAction
{
    public function execute(WeddingBudget $budget): array
    {
        $categories = $budget->activeCategories()
            ->with(['activeItems'])
            ->get();

        return $categories->map(function ($cat) {
            $items       = $cat->activeItems;
            $planned     = $items->sum('planned_amount');
            $actual      = $items->sum(fn ($i) => $i->actual_amount ?? 0);
            $remaining   = $planned - $actual;
            $itemsCount  = $items->count();

            $usagePct = 0;
            if ($planned > 0) {
                $usagePct = min(round(($actual / $planned) * 100, 2), 100);
            }

            $status = $this->resolveStatus($planned, $actual);

            return [
                'id'               => $cat->id,
                'name'             => $cat->name,
                'slug'             => $cat->slug,
                'type'             => $cat->type->value,
                'is_archived'      => $cat->is_archived,
                'planned_total'    => $planned,
                'actual_total'     => $actual,
                'remaining'        => $remaining,
                'usage_percentage' => $usagePct,
                'status'           => $status,
                'status_label'     => $this->statusLabel($status),
                'items_count'      => $itemsCount,
                'formatted'        => [
                    'planned_total' => RupiahFormatter::formatOrZero($planned),
                    'actual_total'  => RupiahFormatter::formatOrZero($actual),
                    'remaining'     => RupiahFormatter::formatOrZero($remaining),
                ],
            ];
        })->values()->toArray();
    }

    private function resolveStatus(int $planned, int $actual): string
    {
        if ($planned <= 0 && $actual <= 0) {
            return 'normal';
        }
        if ($planned > 0 && $actual > $planned) {
            return 'overbudget';
        }
        if ($planned > 0 && ($actual / $planned) >= 0.8) {
            return 'near_limit';
        }
        return 'normal';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'overbudget' => 'Overbudget',
            'near_limit' => 'Mendekati limit',
            default      => 'Normal',
        };
    }
}
