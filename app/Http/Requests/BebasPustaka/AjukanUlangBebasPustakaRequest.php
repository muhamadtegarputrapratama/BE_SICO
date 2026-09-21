<?php

namespace App\Http\Requests\BebasPustaka;

use Illuminate\Foundation\Http\FormRequest;

class AjukanUlangBebasPustakaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi kepemilikan dicek di controller
    }

    public function rules(): array
    {
        return [
            'file_skripsi' => ['required', 'file', 'mimes:pdf', 'max:5120'],
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
