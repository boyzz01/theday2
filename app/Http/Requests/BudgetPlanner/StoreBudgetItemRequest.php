<?php

declare(strict_types=1);

namespace App\Http\Requests\BudgetPlanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'    => ['required', 'integer', Rule::exists('wedding_budget_categories', 'id')],
            'title'          => ['required', 'string', 'max:150'],
            'vendor_name'    => ['nullable', 'string', 'max:150'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'planned_amount' => ['nullable', 'integer', 'min:0'],
            'actual_amount'  => ['nullable', 'integer', 'min:0'],
            'payment_status' => ['required', Rule::in(['unpaid', 'dp', 'paid'])],
            'payment_date'   => ['nullable', 'date'],
            'invitation_id'  => ['nullable', 'integer', 'exists:invitations,id'],
        ];
    }
}
