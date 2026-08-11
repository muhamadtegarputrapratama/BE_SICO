<?php

namespace App\Services;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    use LogsActivity;

    public function register(array $data): array
    {
        $user = User::create([
            'nama' => $data['nama'],
            'nim' => $data['nim'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('mahasiswa');

        $this->logActivity($user, 'Registrasi akun baru sebagai mahasiswa');

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user->load('roles'),
            'token' => $token,
        ];
    }

    public function login(string $login, string $password): array
    {
        $user = User::where('email', $login)
            ->orWhere('nim', $login)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Email/NIM atau password yang Anda masukkan salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->logActivity($user, 'Login ke sistem');

        return [
            'user' => $user->load('roles'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $this->logActivity($user, 'Logout dari sistem');

        $user->currentAccessToken()->delete();
    }
}
