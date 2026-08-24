<?php

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
        Route::post('/', [BebasPustakaController::class, 'store'])
            ->middleware('role:mahasiswa');
        Route::post('/{bebasPustaka}/review', [BebasPustakaController::class, 'review'])
            ->middleware('permission:verifikasi-pustaka');
        Route::post('/{bebasPustaka}/ajukan-ulang', [BebasPustakaController::class, 'ajukanUlang'])
            ->middleware('role:mahasiswa');
    });

    Route::prefix('pengajuan-clearing')->group(function () {
        Route::get('/', [PengajuanClearingController::class, 'index']);
        Route::post('/', [PengajuanClearingController::class, 'store'])
            ->middleware('role:mahasiswa');


        Route::get('/{id}', [PengajuanClearingController::class, 'show']);

        Route::post('/{pengajuan}/ajukan-ulang', [PengajuanClearingController::class, 'ajukanUlang'])
            ->middleware('role:mahasiswa');

        Route::post('/{pengajuan}/review-admin', [PengajuanClearingController::class, 'reviewAdmin'])
            ->middleware('permission:verifikasi-admin');

        Route::get('/{pengajuan}/dokumen/{jenis}', [PengajuanClearingController::class, 'previewDokumen']);

        Route::get('/pengajuan-clearing/{pengajuan}/preview/{jenis}', [PengajuanClearingController::class, 'previewDokumen'])
            ->middleware('auth:sanctum'); // sesuaikan middleware auth

        Route::get('/{pengajuan}/dokumen/{jenis}', [PengajuanClearingController::class, 'previewDokumen']);


        Route::post('/{pengajuan}/review-atasan', [PengajuanClearingController::class, 'reviewAtasan'])
            ->middleware('permission:verifikasi-atasan');

        Route::get('/{pengajuan}/download-surat', [PengajuanClearingController::class, 'downloadSurat']);
    });

    Route::prefix('laporan')
        ->middleware('permission:laporan-view')
        ->group(function () {
            Route::get('/', [LaporanController::class, 'index']);
            Route::get('/export', [LaporanController::class, 'export']);
        });
});
