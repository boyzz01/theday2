<?php

// app/Http/Requests/Invitation/StoreEventRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_name'    => ['required', 'string', 'max:255'],
            'event_date'    => ['required', 'date_format:Y-m-d'],
            'start_time'    => ['nullable', 'date_format:H:i'],
            'end_time'      => ['nullable', 'date_format:H:i', 'after:start_time'],
            'venue_name'    => ['required', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:1000'],
            'maps_url'      => ['nullable', 'url', 'max:2048'],
            'sort_order'    => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_name.required'  => 'Nama acara wajib diisi.',
            'event_date.required'  => 'Tanggal acara wajib diisi.',
            'event_date.date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'start_time.date_format' => 'Format waktu mulai harus HH:MM.',
            'end_time.date_format'   => 'Format waktu selesai harus HH:MM.',
            'end_time.after'         => 'Waktu selesai harus setelah waktu mulai.',
            'venue_name.required'    => 'Nama venue wajib diisi.',
            'maps_url.url'           => 'Link Google Maps harus berupa URL yang valid.',
        ];
    }
}
