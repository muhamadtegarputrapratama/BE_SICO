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
            'file_skripsi' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'file_distribusi' => ['required', 'file', 'mimes:pdf', 'max:1024'],
        ];
    }

    public function messages(): array
    {
        return [
            'file_skripsi.required' => 'File skripsi wajib diunggah.',
            'file_skripsi.mimes' => 'File skripsi harus berformat PDF.',
            'file_skripsi.max' => 'Ukuran file skripsi maksimal 5MB.',
            'file_distribusi.required' => 'File distribusi skripsi wajib diunggah.',
            'file_distribusi.mimes' => 'File distribusi harus berformat PDF.',
            'file_distribusi.max' => 'Ukuran file distribusi maksimal 1MB.',
        ];
    }
}
