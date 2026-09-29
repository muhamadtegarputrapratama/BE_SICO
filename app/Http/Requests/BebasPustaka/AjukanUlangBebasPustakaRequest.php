<?php

namespace App\Http\Requests\BebasPustaka;

use Illuminate\Foundation\Http\FormRequest;

class AjukanUlangBebasPustakaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi kepemilikan dicek di controller
    }

    public function rules(): array
    {
        return [
            'file_skripsi' => [
                'required',
                'file',
                'mimes:pdf',
                'max:5120',
            ],

            'file_distribusi' => [
                'required',
                'file',
                'mimes:pdf',
                'max:1024',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            // File skripsi
            'file_skripsi.required' =>
                'File skripsi hasil revisi wajib diunggah.',

            'file_skripsi.file' =>
                'File skripsi tidak valid.',

            'file_skripsi.mimes' =>
                'File skripsi harus berformat PDF.',

            'file_skripsi.max' =>
                'Ukuran file skripsi maksimal 5MB.',

            // File distribusi
            'file_distribusi.required' =>
                'File distribusi skripsi wajib diunggah.',

            'file_distribusi.file' =>
                'File distribusi skripsi tidak valid.',

            'file_distribusi.mimes' =>
                'File distribusi skripsi harus berformat PDF.',

            'file_distribusi.max' =>
                'Ukuran file distribusi maksimal 1MB.',
        ];
    }
}
