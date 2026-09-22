<?php

namespace App\Http\Requests\BebasPustaka;

use Illuminate\Foundation\Http\FormRequest;

class StoreBebasPustakaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('mahasiswa');
    }

    public function rules(): array
    {
        return [
            // Service selalu butuh file baru saat ajukan ulang, jadi wajib (required)
            'file_skripsi' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_skripsi.required' => 'File skripsi hasil revisi wajib diunggah.',
            'file_skripsi.file' => 'File skripsi tidak valid.',
            'file_skripsi.mimes' => 'File skripsi harus berformat PDF.',
            'file_skripsi.max' => 'Ukuran file skripsi maksimal 5MB.',
        ];
    }
}
