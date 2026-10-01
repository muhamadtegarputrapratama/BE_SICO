<?php

namespace App\Services;

use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    use LogsActivity;

   public function register(array $data): array
{
    $departemen = match (substr($data['nim'], 0, 4)) {
        'E441' => 'Departemen Manajemen Hutan',
        'E442' => 'Departemen Konservasi Sumberdaya Hutan dan Ekowisata',
        'E443' => 'Departemen Silvikultur',
        'E444' => 'Departemen Hasil Hutan',
        default => null,
    };

    $user = User::create([
        'nama' => $data['nama'],
        'nim' => $data['nim'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'departemen' => $departemen,
    ]);

    $user->assignRole('mahasiswa');

    $this->logActivity(
        $user,
        'Registrasi akun baru sebagai mahasiswa'
    );

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
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
