<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BebasPustakaController;
use App\Http\Controllers\Api\PengajuanClearingController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\VerifikasiSuratController;
use App\Http\Controllers\Api\VerifikasiSuratBebasPustakaController;
use App\Http\Controllers\Api\SuratBebasPustakaController;
use App\Http\Controllers\Api\NotifikasiController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('/register', [
        AuthController::class,
        'register'
    ]);

    Route::post('/login', [
        AuthController::class,
        'login'
    ]);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [
            AuthController::class,
            'logout'
        ]);

        Route::get('/me', [
            AuthController::class,
            'me'
        ]);
    });
});


/*
|--------------------------------------------------------------------------
| VERIFIKASI SURAT CLEARING - PUBLIC
|--------------------------------------------------------------------------
*/

Route::get('/surat/verify/{token}', [
    VerifikasiSuratController::class,
    'verify'
])->name('surat.verify');

Route::get('/surat/file/{token}', [
    VerifikasiSuratController::class,
    'file'
])->name('surat.file');


/*
|--------------------------------------------------------------------------
| VERIFIKASI SURAT BEBAS PUSTAKA - PUBLIC
|--------------------------------------------------------------------------
| Tidak membutuhkan login karena diakses dari QR Code.
*/

Route::get('/surat/bebas-pustaka/verify/{token}', [
    VerifikasiSuratBebasPustakaController::class,
    'verify'
])->name('surat.bebas-pustaka.verify');

Route::get('/surat/bebas-pustaka/file/{token}', [
    VerifikasiSuratBebasPustakaController::class,
    'file'
])->name('surat.bebas-pustaka.file');


/*
|--------------------------------------------------------------------------
| ROUTE YANG MEMBUTUHKAN LOGIN
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ]);


    /*
    |--------------------------------------------------------------------------
    | BEBAS PUSTAKA
    |--------------------------------------------------------------------------
    */

    Route::prefix('bebas-pustaka')->group(function () {

        Route::get('/', [
            BebasPustakaController::class,
            'index'
        ]);

        Route::post('/', [
            BebasPustakaController::class,
            'store'
        ])->middleware('role:mahasiswa');

        Route::post('/{bebasPustaka}/review', [
            BebasPustakaController::class,
            'review'
        ])->middleware('permission:verifikasi-pustaka');

        Route::post('/{bebasPustaka}/ajukan-ulang', [
            BebasPustakaController::class,
            'ajukanUlang'
        ])->middleware('role:mahasiswa');


        /*
        |--------------------------------------------------------------------------
        | DOKUMEN BEBAS PUSTAKA
        |--------------------------------------------------------------------------
        */

        Route::get('/{bebasPustaka}/preview-skripsi', [
            BebasPustakaController::class,
            'previewSkripsi'
        ]);

        Route::get('/{bebasPustaka}/preview-distribusi', [
            BebasPustakaController::class,
            'previewDistribusi'
        ]);

        Route::get('/{bebasPustaka}/download', [
            BebasPustakaController::class,
            'download'
        ]);

        Route::get('/{bebasPustaka}/download-distribusi', [
            BebasPustakaController::class,
            'downloadDistribusi'
        ]);


        /*
        |--------------------------------------------------------------------------
        | SURAT BEBAS PUSTAKA
        |--------------------------------------------------------------------------
        */

        Route::get('/{bebasPustaka}/preview-surat', [
            SuratBebasPustakaController::class,
            'previewSurat'
        ]);

        Route::get('/{bebasPustaka}/download-surat', [
            SuratBebasPustakaController::class,
            'downloadSurat'
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | PENGAJUAN CLEARING
    |--------------------------------------------------------------------------
    */

    Route::prefix('pengajuan-clearing')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | LIST & DETAIL
        |--------------------------------------------------------------------------
        */

        Route::get('/', [
            PengajuanClearingController::class,
            'index'
        ]);

        Route::post('/', [
            PengajuanClearingController::class,
            'store'
        ])->middleware('role:mahasiswa');

        Route::get('/{id}', [
            PengajuanClearingController::class,
            'show'
        ]);


        /*
        |--------------------------------------------------------------------------
        | AJUKAN ULANG
        |--------------------------------------------------------------------------
        */

        Route::post('/{pengajuan}/ajukan-ulang', [
            PengajuanClearingController::class,
            'ajukanUlang'
        ])->middleware('role:mahasiswa');


        /*
        |--------------------------------------------------------------------------
        | REVIEW ADMIN
        |--------------------------------------------------------------------------
        */

        Route::post('/{pengajuan}/review-admin', [
            PengajuanClearingController::class,
            'reviewAdmin'
        ])->middleware('permission:verifikasi-admin');


        /*
        |--------------------------------------------------------------------------
        | PREVIEW DOKUMEN
        |--------------------------------------------------------------------------
        */

        Route::get('/{pengajuan}/dokumen/{jenis}', [
            PengajuanClearingController::class,
            'previewDokumen'
        ]);

        // tambah ini (2/10/26)
        Route::get('/{pengajuan}/surat-bebas-pustaka', [
            SuratBebasPustakaController::class,
            'previewDariClearing'
        ]);

        /*
        |--------------------------------------------------------------------------
        | PREVIEW SURAT CLEARING
        |--------------------------------------------------------------------------
        */

        Route::get('/{pengajuan}/preview-surat', [
            PengajuanClearingController::class,
            'previewSurat'
        ]);


        /*
        |--------------------------------------------------------------------------
        | REVIEW ATASAN
        |--------------------------------------------------------------------------
        */

        Route::post('/{pengajuan}/review-atasan', [
            PengajuanClearingController::class,
            'reviewAtasan'
        ])->middleware('permission:verifikasi-atasan');


        /*
        |--------------------------------------------------------------------------
        | QR SURAT CLEARING
        |--------------------------------------------------------------------------
        */

        Route::get('/{pengajuan}/qr', [
            PengajuanClearingController::class,
            'showQR'
        ])->name('pengajuan-clearing.qr');


        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD SURAT CLEARING
        |--------------------------------------------------------------------------
        */

        Route::get('/{pengajuan}/download-surat', [
            PengajuanClearingController::class,
            'downloadSurat'
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | NOTIFIKASI
    |--------------------------------------------------------------------------
    */

    Route::prefix('notifikasi')->group(function () {

        Route::get('/', [
            NotifikasiController::class,
            'index'
        ]);

        Route::get('/unread-count', [
            NotifikasiController::class,
            'unreadCount'
        ]);

        Route::post('/{id}/read', [
            NotifikasiController::class,
            'markRead'
        ]);

        Route::post('/read-all', [
            NotifikasiController::class,
            'markAllRead'
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | LAPORAN
    |--------------------------------------------------------------------------
    */

    Route::prefix('laporan')
        ->middleware('permission:laporan-view')
        ->group(function () {

            Route::get('/', [
                LaporanController::class,
                'index'
            ]);

            Route::get('/export', [
                LaporanController::class,
                'export'
            ]);
        });
});