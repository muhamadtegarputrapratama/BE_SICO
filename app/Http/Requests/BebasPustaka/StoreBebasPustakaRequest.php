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
            // (ubah ke 20480 untuk 20 MB)
            'file_skripsi' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_skripsi.required' => 'File skripsi wajib diunggah.',
            'file_skripsi.file' => 'File skripsi tidak valid.',
            'file_skripsi.mimes' => 'File skripsi harus berformat PDF.',
            'file_skripsi.max' => 'Ukuran file skripsi maksimal 10 MB.',
            'file_skripsi.uploaded' => 'File gagal diunggah. Ukuran melebihi batas server.',
        ];
    }

     public function messages(): array
    {
        return [
            'file_skripsi.required' => 'File skripsi wajib diunggah.',
            'file_skripsi.mimes' => 'File skripsi harus berformat PDF.',
            'file_skripsi.max' => 'Ukuran file skripsi maksimal 5MB.',
        ];
    }
}
