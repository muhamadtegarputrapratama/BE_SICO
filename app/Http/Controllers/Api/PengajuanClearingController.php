<?php

namespace App\Http\Controllers\Api;

use App\Enums\PengajuanClearingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PengajuanClearing\StorePengajuanClearingRequest;
use App\Http\Requests\ReviewRequest;
use App\Models\PengajuanClearing;
use App\Services\PengajuanClearingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PengajuanClearingController extends Controller
{
    use ApiResponse;

    public function __construct(protected PengajuanClearingService $service)
    { 
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PengajuanClearing::with(['user', 'admin', 'atasan']);

        $query = match (true) {
            $user->hasAnyRole(['admin', 'atasan']) => $query->latest(),
            default => $query->where('user_id', $user->id)->latest(),
        };

        return $this->success('Data pengajuan clearing berhasil diambil.', $query->paginate(15));
    }

    public function store(StorePengajuanClearingRequest $request): JsonResponse
    {
        $pengajuan = $this->service->ajukan($request->user(), $request->validated());

        return $this->success('Pengajuan clearing berhasil dibuat.', $pengajuan, 201);
    }

    public function show($id)
    {
        $data = PengajuanClearing::with('user', 'bebasPustaka')->find($id);
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function ajukanUlang(Request $request, PengajuanClearing $pengajuan): JsonResponse
    {
        if ($pengajuan->user_id !== $request->user()->id) {
            return $this->error('Anda tidak memiliki akses.', null, 403);
        }

        $data = $request->validate([
            'departemen' => ['sometimes', 'string', 'max:255'],
            'program_studi' => ['sometimes', 'string', 'max:255'], 
            'file_ktm' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'file_bukti_spp' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'file_distribusi' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $pengajuan = $this->service->ajukanUlang($pengajuan, $request->user(), $data);

        return $this->success('Pengajuan clearing berhasil diajukan ulang.', $pengajuan);
    }

    public function reviewAdmin(ReviewRequest $request, $pengajuan): JsonResponse
    {
        if (! $request->user()->hasRole('admin')) {
            return $this->error('Anda tidak memiliki akses.', null, 403);
        }

        $pengajuanModel = PengajuanClearing::find($pengajuan);

        if (! $pengajuanModel) {
            return $this->error("Data pengajuan clearing dengan ID {$pengajuan} tidak ditemukan.", null, 404);
        }

        $pengajuanModel = $this->service->reviewAdmin(
            $pengajuanModel,
            $request->user(),
            $request->validated('keputusan'),
            $request->validated('catatan_revisi')
        );

        return $this->success('Review admin berhasil disimpan.', $pengajuanModel);
    }

    public function reviewAtasan(Request $request, $pengajuan): JsonResponse
    {
        if (! $request->user()->hasRole('atasan')) {
            return $this->error('Anda tidak memiliki akses.', null, 403);
        }

        $pengajuanModel = PengajuanClearing::find($pengajuan);

        if (! $pengajuanModel) {
            return $this->error("Data pengajuan clearing dengan ID {$pengajuan} tidak ditemukan.", null, 404);
        }

        $data = $request->validate([
            'keputusan' => ['required', 'in:setuju,tolak'],
        ]);

        try {
            $pengajuanModel = $this->service->reviewAtasan($pengajuanModel, $request->user(), $data['keputusan']);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        return $this->success('Review atasan berhasil disimpan.', $pengajuanModel);
    }

    public function downloadSurat(Request $request, $pengajuan)
    {
        $pengajuanModel = PengajuanClearing::find($pengajuan);

        if (! $pengajuanModel) {
            return $this->error("Data pengajuan clearing dengan ID {$pengajuan} tidak ditemukan.", null, 404);
        }

        $user = $request->user();
        $bolehAkses = $pengajuanModel->user_id === $user->id || $user->hasAnyRole(['admin', 'atasan']);

        if (! $bolehAkses) {
            return $this->error("Anda tidak memiliki akses untuk mengunduh surat ini.", null, 403);
        }

        if (! $pengajuanModel->file_surat) {
            return $this->error("File surat belum diterbitkan/dibuat di database.", null, 404);
        }

        // Menggunakan path absolut langsung ke storage/app/
        $fullPath = storage_path('app/' . $pengajuanModel->file_surat);

        if (! file_exists($fullPath)) {
            return $this->error("File surat fisik tidak ditemukan di direktori storage server ({$fullPath}).", null, 404);
        }

        $cleanNomorSurat = str_replace('/', '_', $pengajuanModel->nomor_surat ?? "clearing-{$pengajuanModel->id}");
        $namaFileDownload = "{$cleanNomorSurat}.docx";

        if (ob_get_level()) {
            ob_end_clean();
        }

        return response()->download($fullPath, $namaFileDownload, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $namaFileDownload . '"',
            'Content-Length' => filesize($fullPath),
        ]);
    }

    // NEW - preview/serve dokumen persyaratan (KTM, SPP, Distribusi)
    public function previewDokumen(Request $request, $pengajuan, string $jenis)
    {
        $pengajuanModel = PengajuanClearing::find($pengajuan);

        if (! $pengajuanModel) {
            return $this->error("Data pengajuan tidak ditemukan.", null, 404);
        }

        $user = $request->user();
        $bolehAkses = $pengajuanModel->user_id === $user->id || $user->hasAnyRole(['admin', 'atasan']);

        if (! $bolehAkses) {
            return $this->error("Anda tidak memiliki akses ke dokumen ini.", null, 403);
        }

        $fieldMap = [
            'ktm' => 'file_ktm',
            'spp' => 'file_bukti_spp',
            'distribusi' => 'file_distribusi',
        ];

        if (! isset($fieldMap[$jenis])) {
            return $this->error("Jenis dokumen tidak valid.", null, 404);
        }

        $path = $pengajuanModel->{$fieldMap[$jenis]};

        // Cek file pada disk 'public' atau 'local' (sesuaikan dengan disk tempat upload)
        $disk = Storage::disk('public')->exists($path) ? 'public' : (Storage::disk('local')->exists($path) ? 'local' : null);

        if (! $disk || ! $path) {
            return $this->error("File tidak ditemukan di penyimpanan server.", null, 404);
        }

        $filePath = Storage::disk($disk)->path($path);

        return response()->file($filePath);
    }
}