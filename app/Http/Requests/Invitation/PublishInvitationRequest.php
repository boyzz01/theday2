<?php

// app/Http/Requests/Invitation/PublishInvitationRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invitationId = $this->route('invitation')?->id;

        return [
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('invitations', 'slug')->ignore($invitationId),
            ],
            'is_password_protected' => ['sometimes', 'boolean'],
            'password'              => [
                'nullable',
                Rule::requiredIf(fn () => (bool) $this->input('is_password_protected')),
                'string',
                'min:4',
                'max:50',
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'         => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'slug.unique'        => 'URL ini sudah digunakan undangan lain.',
            'password.required'  => 'Password wajib diisi saat proteksi password diaktifkan.',
            'password.min'       => 'Password minimal 4 karakter.',
            'expires_at.after'   => 'Tanggal kedaluwarsa harus di masa mendatang.',
        ];
    }
}
