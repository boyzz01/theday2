<?php

// app/Http/Requests/Invitation/StoreInvitationRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate::allows('create', Invitation::class) handled in controller
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'uuid', 'exists:templates,id'],
            'title'       => ['required', 'string', 'min:3', 'max:255'],
            'event_type'  => ['required', Rule::enum(EventType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => 'Template harus dipilih.',
            'template_id.exists'   => 'Template tidak ditemukan.',
            'title.required'       => 'Judul undangan wajib diisi.',
            'title.min'            => 'Judul minimal 3 karakter.',
            'title.max'            => 'Judul maksimal 255 karakter.',
            'event_type.required'  => 'Jenis acara wajib dipilih.',
        ];
    }
}
