<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BebasPustaka;
use App\Services\SuratBebasPustakaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class VerifikasiSuratBebasPustakaController extends Controller
{
    use ApiResponse;

    public function verify(string $token): JsonResponse
    {
        $pustaka = BebasPustaka::with('user')->where('qr_token', $token)->first();

        if (! $pustaka) {
            return $this->error('Surat tidak ditemukan atau tidak valid.', null, 404);
        }

        return $this->success('Surat valid.', [
            'id' => $pustaka->id,
            'nomor_surat' => $pustaka->nomor_surat,
            'nama' => $pustaka->user->nama,
            'nim' => $pustaka->user->nim,
            'departemen' => $pustaka->user->departemen,
            'diterbitkan_pada' => $pustaka->updated_at?->format('d-m-Y H:i'),
        ]);
    }

    public function file(string $token)
    {
        $pustaka = BebasPustaka::where('qr_token', $token)->first();

        if (! $pustaka || ! $pustaka->file_surat) {
            abort(404, 'Surat tidak ditemukan atau tidak valid.');
        }

        if (! Storage::disk('public')->exists($pustaka->file_surat)) {
            abort(404, 'File surat tidak ditemukan.');
        }

        return response()->file(
            Storage::disk('public')->path($pustaka->file_surat),
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Gambar QR (SVG) untuk ditampilkan di frontend lewat <img src="...">.
     */
    public function qr(string $token, SuratBebasPustakaService $service)
    {
        $pustaka = BebasPustaka::where('qr_token', $token)->first();

        if (! $pustaka) {
            abort(404, 'QR Code tidak ditemukan.');
        }

        return $service->generateQR($pustaka);
    }
}