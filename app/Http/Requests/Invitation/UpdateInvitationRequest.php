<?php

// app/Http/Requests/Invitation/UpdateInvitationRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use App\Enums\EventType;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('expires_at')) {
            try {
                $this->merge([
                    'expires_at' => Carbon::parse($this->expires_at)->format('Y-m-d H:i:s'),
                ]);
            } catch (\Exception) {
                // biarkan validasi 'date' yang menolak
            }
        }
    }

    public function rules(): array
    {
        $invitationId = $this->route('invitation')?->id;

        return [
            'title'      => ['sometimes', 'string', 'min:3', 'max:255'],
            'event_type' => ['sometimes', Rule::enum(EventType::class)],
            'slug'       => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('invitations', 'slug')->ignore($invitationId),
            ],
            'current_step'                => ['sometimes', 'integer', 'min:0', 'max:6'],
            'expires_at'                  => ['sometimes', 'nullable', 'date'],
            'is_password_protected'       => ['sometimes', 'boolean'],
            'password'                    => ['sometimes', 'nullable', 'string', 'min:4'],
            'custom_config'               => ['sometimes', 'array'],
            'custom_config.primary_color' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
            'custom_config.font'          => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'URL ini sudah digunakan undangan lain.',
            'custom_config.primary_color.regex' => 'Format warna harus berupa hex (contoh: #D4A373).',
        ];
    }
}
