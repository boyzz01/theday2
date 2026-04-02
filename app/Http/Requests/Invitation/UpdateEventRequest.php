<?php

// app/Http/Requests/Invitation/UpdateEventRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_name'    => ['sometimes', 'string', 'max:255'],
            'event_date'    => ['sometimes', 'date_format:Y-m-d'],
            'start_time'    => ['nullable', 'date_format:H:i'],
            'end_time'      => ['nullable', 'date_format:H:i', 'after:start_time'],
            'venue_name'    => ['sometimes', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:1000'],
            'maps_url'      => ['nullable', 'url', 'max:2048'],
            'sort_order'    => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_date.date_format'  => 'Format tanggal harus YYYY-MM-DD.',
            'start_time.date_format'  => 'Format waktu mulai harus HH:MM.',
            'end_time.date_format'    => 'Format waktu selesai harus HH:MM.',
            'end_time.after'          => 'Waktu selesai harus setelah waktu mulai.',
            'maps_url.url'            => 'Link Google Maps harus berupa URL yang valid.',
        ];
    }
}
