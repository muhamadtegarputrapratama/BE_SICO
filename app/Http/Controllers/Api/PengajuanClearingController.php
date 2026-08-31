<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PengajuanClearing\StorePengajuanClearingRequest;
use App\Http\Requests\ReviewRequest;
use App\Models\PengajuanClearing;
use App\Services\PengajuanClearingService;
use App\Services\SuratClearingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class PengajuanClearingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PengajuanClearingService $service,
        protected SuratClearingService $suratService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PengajuanClearing::with([
            'user',
            'admin',
            'atasan'
        ]);

        $query = match (true) {
            $user->hasAnyRole(['admin', 'atasan'])
                => $query->latest(),

            default
                => $query
                    ->where('user_id', $user->id)
                    ->latest(),
        };

        return $this->success(
            'Data pengajuan clearing berhasil diambil.',
            $query->paginate(15)
        );
    }

    public function store(
        StorePengajuanClearingRequest $request
    ): JsonResponse {
        $pengajuan = $this->service->ajukan(
            $request->user(),
            $request->validated()
        );

        return $this->success(
            'Pengajuan clearing berhasil dibuat.',
            $pengajuan,
            201
        );
    }

    public function show($id)
    {
        $data = PengajuanClearing::with([
            'user',
            'bebasPustaka'
        ])->find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function ajukanUlang(
        Request $request,
        PengajuanClearing $pengajuan
    ): JsonResponse {
        if ($pengajuan->user_id !== $request->user()->id) {
            return $this->error(
                'Anda tidak memiliki akses.',
                null,
                403
            );
        }

        $data = $request->validate([
            'departemen' => [
                'sometimes',
                'string',
                'max:255'
            ],

            'program_studi' => [
                'sometimes',
                'string',
                'max:255'
            ],

            'file_ktm' => [
                'sometimes',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048'
            ],

            'file_bukti_spp' => [
                'sometimes',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048'
            ],

            'file_distribusi' => [
                'sometimes',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048'
            ],
        ]);

        $pengajuan = $this->service->ajukanUlang(
            $pengajuan,
            $request->user(),
            $data
        );

        return $this->success(
            'Pengajuan clearing berhasil diajukan ulang.',
            $pengajuan
        );
    }

    public function reviewAdmin(
        ReviewRequest $request,
        $pengajuan
    ): JsonResponse {
        if (!$request->user()->can('verifikasi-admin')) {
            return $this->error(
                'Anda tidak memiliki akses.',
                null,
                403
            );
        }

        $pengajuanModel = PengajuanClearing::find($pengajuan);

        if (!$pengajuanModel) {
            return $this->error(
                "Data pengajuan clearing dengan ID {$pengajuan} tidak ditemukan.",
                null,
                404
            );
        }

        $pengajuanModel = $this->service->reviewAdmin(
            $pengajuanModel,
            $request->user(),
            $request->validated('keputusan'),
            $request->validated('catatan_revisi')
        );

        return $this->success(
            'Review admin berhasil disimpan.',
            $pengajuanModel
        );
    }

    public function reviewAtasan(Request $request, $pengajuan): JsonResponse
{
    if (!$request->user()->hasRole('atasan')) {
        return $this->error(
            'Anda tidak memiliki akses.',
            null,
            403
        );
    }

    $pengajuanModel = PengajuanClearing::find($pengajuan);

    if (!$pengajuanModel) {
        return $this->error(
            "Data pengajuan clearing dengan ID {$pengajuan} tidak ditemukan.",
            null,
            404
        );
    }

    $data = $request->validate([
        'keputusan' => ['required', 'in:setuju,tolak'],
    ]);

    try {

        $pengajuanModel = $this->service->reviewAtasan(
            $pengajuanModel,
            $request->user(),
            $data['keputusan']
        );

        /*
        |--------------------------------------------------------------------------
        | Kalau Atasan SETUJU
        |--------------------------------------------------------------------------
        | Baru generate surat FINAL.
        */
        if ($data['keputusan'] === 'setuju') {

            $pengajuanModel = $this->suratService->generate(
                $pengajuanModel
            );
        }

    } catch (ValidationException $e) {

        return $this->error(
            $e->getMessage(),
            $e->errors(),
            422
        );
    }

    return $this->success(
        'Review atasan berhasil disimpan.',
        $pengajuanModel
    );
}

 public function previewSurat(Request $request, $pengajuan)
{
    try {
        // Ambil token dari query ?token= atau Bearer Token
        $token = $request->query('token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan.'
            ], 401);
        }

        // Validasi token Sanctum
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid.'
            ], 401);
        }

        $user = $accessToken->tokenable;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.'
            ], 401);
        }

        // Cari pengajuan
        $pengajuanModel = PengajuanClearing::with('user')
            ->find($pengajuan);

        if (!$pengajuanModel) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan tidak ditemukan.'
            ], 404);
        }

        // Cek akses
        $bolehAkses =
            (int) $pengajuanModel->user_id === (int) $user->id ||
            $user->hasAnyRole(['admin', 'atasan']);

        if (!$bolehAkses) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke surat ini.'
            ], 403);
        }

        // Generate PDF preview
        $pdf = $this->suratService->preview($pengajuanModel);

        return $pdf->stream(
            "preview-surat-clearing-{$pengajuanModel->id}.pdf"
        );

    } catch (\Throwable $e) {

        \Log::error('Preview Surat Error', [
            'pengajuan_id' => $pengajuan,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}
public function downloadSurat($pengajuan, Request $request)
{
    try {
        $token = $request->query('token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan.'
            ], 401);
        }

        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan atau token tidak valid.'
            ], 401);
        }

        if (!$pengajuan || $pengajuan == 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID pengajuan tidak valid.'
            ], 400);
        }

        $pengajuanModel = PengajuanClearing::find($pengajuan);

        if (!$pengajuanModel) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan tidak ditemukan.'
            ], 404);
        }

        if (!$pengajuanModel->file_surat) {
            return response()->json([
                'success' => false,
                'message' => 'Surat belum tersedia.'
            ], 404);
        }

        if (!Storage::disk('public')->exists($pengajuanModel->file_surat)) {
            return response()->json([
                'success' => false,
                'message' => 'File surat tidak ditemukan.'
            ], 404);
        }

        $path = Storage::disk('public')->path($pengajuanModel->file_surat);

        return response()->download($path, 'surat-clearing.pdf');

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}

    public function previewDokumen(
        Request $request,
        $pengajuan,
        string $jenis
    ) {
        $pengajuanModel = PengajuanClearing::find($pengajuan);

        if (!$pengajuanModel) {
            return $this->error(
                'Data pengajuan tidak ditemukan.',
                null,
                404
            );
        }

        $user = $request->user();

        $bolehAkses =
            $pengajuanModel->user_id === $user->id ||
            $user->hasAnyRole(['admin', 'atasan']);

        if (!$bolehAkses) {
            return $this->error(
                'Anda tidak memiliki akses ke dokumen ini.',
                null,
                403
            );
        }

        $fieldMap = [
            'ktm' => 'file_ktm',
            'spp' => 'file_bukti_spp',
            'distribusi' => 'file_distribusi',
        ];

        if (!isset($fieldMap[$jenis])) {
            return $this->error(
                'Jenis dokumen tidak valid.',
                null,
                404
            );
        }

        $path = $pengajuanModel->{$fieldMap[$jenis]};

        if (
            !$path ||
            !Storage::disk('public')->exists($path)
        ) {
            return $this->error(
                'File tidak ditemukan.',
                null,
                404
            );
        }

        return response()->file(
            Storage::disk('public')->path($path)
        );
    }
}
