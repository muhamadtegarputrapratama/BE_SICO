<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService)
    {}

    public function register(RegisterRequest $request): JsonResponse
{
    $validated = $request->validated();

    // Menentukan departemen berdasarkan kode NIM
    $validated['departemen'] = match (substr($validated['nim'], 0, 4)) {
        'E441' => 'Departemen Manajemen Hutan',
        'E442' => 'Departemen Konservasi Sumberdaya Hutan dan Ekowisata',
        'E443' => 'Departemen Silvikultur',
        'E444' => 'Departemen Hasil Hutan',
        default => null,
    };

    $result = $this->authService->register($validated);

    return $this->success('Registrasi berhasil.', [
        'user' => new UserResource($result['user']),
        'token' => $result['token'],
    ], 201);
}

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->authService->login(
            $validated['login'],
            $validated['password'],
        );

        return $this->success('Login berhasil.', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success('Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            'Data user berhasil diambil.',
            new UserResource($request->user()->load('roles'))
        );
    }
}
