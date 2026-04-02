<?php

// app/Http/Requests/Invitation/StoreMusicRequest.php

declare(strict_types=1);

namespace App\Http\Requests\Invitation;

use Illuminate\Foundation\Http\FormRequest;

class StoreMusicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'default' = pick from preset list, 'upload' = upload own file
            'type'  => ['required', 'in:default,upload'],

            // Required when type = default
            'title' => ['required_if:type,default', 'nullable', 'string', 'max:255'],

            // Required when type = upload
            'file'  => [
                'required_if:type,upload',
                'nullable',
                'file',
                'mimes:mp3,wav,ogg,m4a,aac',
                'max:10240', // 10 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'          => 'Tipe musik wajib ditentukan.',
            'type.in'                => 'Tipe musik tidak valid.',
            'title.required_if'      => 'Judul musik wajib diisi untuk pilihan preset.',
            'file.required_if'       => 'File audio wajib diunggah.',
            'file.mimes'             => 'Format audio harus MP3, WAV, OGG, M4A, atau AAC.',
            'file.max'               => 'Ukuran file audio maksimal 10 MB.',
        ];
    }
}
