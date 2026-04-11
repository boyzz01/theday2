<?php

declare(strict_types=1);

namespace App\Http\Requests\BudgetPlanner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:100'],
            'is_archived' => ['sometimes', 'boolean'],
        ];
    }
}
