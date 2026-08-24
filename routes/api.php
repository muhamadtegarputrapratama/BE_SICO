<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BebasPustakaController;
use App\Http\Controllers\Api\PengajuanClearingController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\VerifikasiSuratController;


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

Route::get('/surat/verify/{token}', [VerifikasiSuratController::class, 'verify'])
    ->name('surat.verify');

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('bebas-pustaka')->group(function () {
        Route::get('/', [BebasPustakaController::class, 'index']);
        Route::post('/', [BebasPustakaController::class, 'store']);
        Route::post('/{bebasPustaka}/review', [BebasPustakaController::class, 'review'])
            ->middleware('role:pustakawan');
        Route::post('/{bebasPustaka}/ajukan-ulang', [BebasPustakaController::class, 'ajukanUlang']);
    });

    Route::prefix('pengajuan-clearing')->group(function () {
        Route::get('/', [PengajuanClearingController::class, 'index']);
        Route::post('/', [PengajuanClearingController::class, 'store']);
        Route::get('/pengajuan-clearing/{id}', [PengajuanClearingController::class, 'show']);
        Route::post('/{pengajuan}/ajukan-ulang', [PengajuanClearingController::class, 'ajukanUlang']);
        Route::post('/{pengajuan}/review-admin', [PengajuanClearingController::class, 'reviewAdmin'])
            ->middleware('role:admin');
        Route::post('/{pengajuan}/review-atasan', [PengajuanClearingController::class, 'reviewAtasan'])
            ->middleware('role:atasan');
        Route::get('/{pengajuan}/download-surat', [PengajuanClearingController::class, 'downloadSurat']);
    });

    Route::prefix('laporan')->middleware('role:admin')->group(function () {
        Route::get('/', [LaporanController::class, 'index']);
        Route::get('/export', [LaporanController::class, 'export']);
    });
});
