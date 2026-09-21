<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255', 'regex:/^\p{Lu}/u'],
            'nim' => ['required', 'string', 'max:20', 'regex:/^E[A-Za-z0-9]+$/', 'unique:users,nim'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama.regex' => 'Huruf pertama nama harus kapital.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.regex' => 'NIM harus diawali huruf E kapital, diikuti huruf atau angka.',
            'nim.unique' => 'NIM sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}