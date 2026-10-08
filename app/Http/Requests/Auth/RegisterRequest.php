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
            'nim' => ['required', 'string', 'size:11', 'regex:/^E[1-4]41[0-9]{7}$/', 'unique:users,nim'],
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
            'nim.size' => 'NIM harus terdiri dari tepat 11 karakter.',
            'nim.regex' => 'NIM harus diawali kode yang valid (E141, E241, E341, atau E441).',
            'nim.unique' => 'NIM sudah terdaftar.',

            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
