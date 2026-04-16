<?php

// app/Http/Requests/ContactRequest.php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'min:2', 'max:100'],
            'email'   => ['required', 'email', 'max:255'],
            'subject' => ['required', 'in:Pertanyaan umum,Masalah teknis,Pembayaran & langganan,Saran & masukan,Kerjasama & partnership,Lainnya'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            // honeypot — must be empty
            'website' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Nama kamu wajib diisi.',
            'name.min'         => 'Nama minimal 2 karakter.',
            'name.max'         => 'Nama terlalu panjang.',
            'email.required'   => 'Alamat email wajib diisi.',
            'email.email'      => 'Format email tidak valid.',
            'subject.required' => 'Pilih topik pesan.',
            'subject.in'       => 'Topik tidak valid.',
            'message.required' => 'Pesan wajib diisi.',
            'message.min'      => 'Pesan minimal 10 karakter.',
            'message.max'      => 'Pesan maksimal 2000 karakter.',
            'website.max'      => 'Terjadi kesalahan.',
        ];
    }
}
