<?php

// app/Http/Requests/Invitation/ReorderGalleryRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class ReorderGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'  => 'Daftar urutan foto wajib diisi.',
            'ids.array'     => 'Format urutan tidak valid.',
            'ids.*.uuid'    => 'ID foto tidak valid.',
        ];
    }
}
