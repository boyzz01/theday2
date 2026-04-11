<?php

declare(strict_types=1);

namespace App\Actions\BudgetPlanner;

use App\Models\WeddingBudget;
use App\Support\Formatters\RupiahFormatter;

final class BuildBudgetSummaryAction
{
    public function execute(WeddingBudget $budget): array
    {
        $items = $budget->activeItems()->get();

        $totalPlanned = $items->sum('planned_amount');
        $totalActual  = $items->sum(fn ($i) => $i->actual_amount ?? 0);

        $totalBudget      = $budget->total_budget;
        $remainingBudget  = $totalBudget !== null ? ($totalBudget - $totalActual) : null;
        $plannedVsActual  = $totalPlanned - $totalActual;
        $isOverbudget     = $totalBudget !== null && $totalActual > $totalBudget;
        $overbudgetAmount = $isOverbudget ? ($totalActual - $totalBudget) : 0;

        $usagePercentage = null;
        if ($totalBudget !== null && $totalBudget > 0) {
            $usagePercentage = min(round(($totalActual / $totalBudget) * 100, 2), 100);
        }

        // Count overbudget categories
        $overbudgetCategoriesCount = $budget->activeCategories()
            ->withSum(['activeItems' => fn ($q) => $q->whereNotNull('actual_amount')], 'actual_amount')
            ->withSum('activeItems', 'planned_amount')
            ->get()
            ->filter(fn ($cat) => ($cat->active_items_sum_planned_amount ?? 0) > 0
                && ($cat->active_items_sum_actual_amount ?? 0) > ($cat->active_items_sum_planned_amount ?? 0))
            ->count();

        return [
            'total_budget'               => $totalBudget,
            'total_planned'              => $totalPlanned,
            'total_actual'               => $totalActual,
            'remaining_budget'           => $remainingBudget,
            'planned_vs_actual_gap'      => $plannedVsActual,
            'usage_percentage'           => $usagePercentage,
            'is_total_overbudget'        => $isOverbudget,
            'overbudget_amount'          => $overbudgetAmount,
            'overbudget_categories_count' => $overbudgetCategoriesCount,
            'has_budget'                 => $totalBudget !== null,
            'formatted'                  => [
                'total_budget'          => RupiahFormatter::format($totalBudget),
                'total_planned'         => RupiahFormatter::formatOrZero($totalPlanned),
                'total_actual'          => RupiahFormatter::formatOrZero($totalActual),
                'remaining_budget'      => $remainingBudget !== null ? RupiahFormatter::format($remainingBudget) : null,
                'planned_vs_actual_gap' => RupiahFormatter::formatOrZero($plannedVsActual),
                'overbudget_amount'     => RupiahFormatter::formatOrZero($overbudgetAmount),
            ],
        ];
    }
}
