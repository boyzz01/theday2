<?php

// app/Http/Requests/Invitation/StoreDetailRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;

class StoreDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invitation = $this->route('invitation');
        $isWedding  = $invitation?->event_type === EventType::Pernikahan;

        if ($isWedding) {
            return $this->weddingRules();
        }

        return $this->birthdayRules();
    }

    private function weddingRules(): array
    {
        return [
            'groom_name'         => ['nullable', 'string', 'max:255'],
            'bride_name'         => ['nullable', 'string', 'max:255'],
            'groom_parent_names' => ['nullable', 'string', 'max:255'],
            'bride_parent_names' => ['nullable', 'string', 'max:255'],
            'groom_photo'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'bride_photo'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_photo'        => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'opening_text'       => ['nullable', 'string', 'max:2000'],
            'closing_text'       => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function birthdayRules(): array
    {
        return [
            'birthday_person_name' => ['nullable', 'string', 'max:255'],
            'birthday_age'         => ['nullable', 'integer', 'min:1', 'max:200'],
            'birthday_photo'       => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_photo'          => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'opening_text'         => ['nullable', 'string', 'max:2000'],
            'closing_text'         => ['nullable', 'string', 'max:2000'],
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
