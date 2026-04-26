<?php

// app/Http/Requests/Invitation/StoreDetailRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class StoreDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'groom_name'         => ['nullable', 'string', 'max:255'],
            'groom_nickname'     => ['nullable', 'string', 'max:50'],
            'groom_instagram'    => ['nullable', 'string', 'max:100'],
            'bride_name'         => ['nullable', 'string', 'max:255'],
            'bride_nickname'     => ['nullable', 'string', 'max:50'],
            'bride_instagram'    => ['nullable', 'string', 'max:100'],
            'groom_parent_names' => ['nullable', 'string', 'max:255'],
            'bride_parent_names' => ['nullable', 'string', 'max:255'],
            'groom_photo'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'bride_photo'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_photo'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'opening_text'       => ['nullable', 'string', 'max:2000'],
            'closing_text'       => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.image'  => ':attribute harus berupa file gambar.',
            '*.mimes'  => ':attribute harus berformat JPG, PNG, atau WebP.',
            '*.max'    => ':attribute maksimal 5 MB.',
        ];
    }
}
