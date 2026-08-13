<?php
// app/Http/Requests/PengajuanClearing/StorePengajuanClearingRequest.php

namespace App\Http\Requests\PengajuanClearing;

use Illuminate\Foundation\Http\FormRequest;

class StorePengajuanClearingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('mahasiswa');
    }

    public function rules(): array
    {
        return [
            'departemen' => ['required', 'string', 'max:255'],
            'program_studi' => ['required', 'string', 'max:255'],
            'file_ktm' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'file_bukti_spp' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'file_distribusi' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'departemen.required' => 'Departemen wajib diisi.',
            'program_studi.required' => 'Program studi wajib diisi.',
            'file_ktm.required' => 'File KTM wajib diunggah.',
            'file_bukti_spp.required' => 'File bukti pembayaran SPP wajib diunggah.',
            'file_distribusi.required' => 'File distribusi wajib diunggah.',
        ];
    }
}