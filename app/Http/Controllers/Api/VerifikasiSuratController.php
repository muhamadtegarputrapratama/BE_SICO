<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengajuanClearing;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class VerifikasiSuratController extends Controller
{
    use ApiResponse;

    public function verify(string $token): JsonResponse
    {
        $pengajuan = PengajuanClearing::with('user')->where('qr_token', $token)->first();

        if (! $pengajuan) {
            return $this->error('Surat tidak ditemukan atau tidak valid.', null, 404);
        }

        return $this->success('Surat valid.', [
            'id' => $pengajuan->id,
            'qr_token' => $pengajuan->qr_token,
            'nomor_surat' => $pengajuan->nomor_surat,
            'nama' => $pengajuan->user->nama,
            'nim' => $pengajuan->user->nim,
            'program_studi' => $pengajuan->program_studi,
            'departemen' => $pengajuan->departemen,
            'diterbitkan_pada' => $pengajuan->disetujui_atasan_at?->format('d-m-Y H:i'),
        ]);
    }

    public function file(string $token)
    {
        $pengajuan = PengajuanClearing::where('qr_token', $token)->first();

        if (! $pengajuan || ! $pengajuan->file_surat) {
            abort(404, 'Surat tidak ditemukan atau tidak valid.');
        }

        if (! Storage::disk('public')->exists($pengajuan->file_surat)) {
            abort(404, 'File surat tidak ditemukan.');
        }

        return response()->file(
            Storage::disk('public')->path($pengajuan->file_surat)
        );
    }
}
