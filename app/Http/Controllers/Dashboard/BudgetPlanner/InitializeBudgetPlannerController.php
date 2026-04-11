<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard\BudgetPlanner;

use App\Actions\BudgetPlanner\InitializeWeddingBudgetAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InitializeBudgetPlannerController extends Controller
{
    public function __construct(
        private readonly InitializeWeddingBudgetAction $action,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $budget = $this->action->execute($request->user());

        return response()->json([
            'message'   => 'Budget planner berhasil diinisialisasi.',
            'budget_id' => $budget->id,
        ], 201);
    }
}
