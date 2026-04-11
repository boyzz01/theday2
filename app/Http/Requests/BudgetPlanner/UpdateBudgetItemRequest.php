<?php

declare(strict_types=1);

namespace App\Http\Requests\BudgetPlanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'    => ['sometimes', 'integer', Rule::exists('wedding_budget_categories', 'id')],
            'title'          => ['sometimes', 'string', 'max:150'],
            'vendor_name'    => ['nullable', 'string', 'max:150'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'planned_amount' => ['nullable', 'integer', 'min:0'],
            'actual_amount'  => ['nullable', 'integer', 'min:0'],
            'payment_status' => ['sometimes', Rule::in(['unpaid', 'dp', 'paid'])],
            'payment_date'   => ['nullable', 'date'],
            'is_archived'    => ['sometimes', 'boolean'],
        ];
    }
}
