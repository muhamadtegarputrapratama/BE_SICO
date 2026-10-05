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

            'file_ktm' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'file_bukti_spp' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [

            'file_ktm.required' => 'File KTM wajib diunggah.',
            'file_bukti_spp.required' => 'File bukti pembayaran SPP wajib diunggah.',
            'file_ktm.max' => 'Ukuran file KTM maksimal 2MB.',
            'file_bukti_spp.max' => 'Ukuran file bukti pembayaran SPP maksimal 2MB.',
            'file_ktm.mimes' => 'File KTM harus berformat JPG, JPEG, PNG, atau PDF.',
            'file_bukti_spp.mimes' => 'File bukti pembayaran SPP harus berformat JPG, JPEG, PNG, atau PDF.',
        ];
    }
}
