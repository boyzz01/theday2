<?php

declare(strict_types=1);

namespace App\Http\Requests\BudgetPlanner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_budget' => ['nullable', 'integer', 'min:0'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}
