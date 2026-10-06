<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BebasPustaka;
use App\Models\PengajuanClearing;
use App\Services\SuratBebasPustakaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SuratBebasPustakaController extends Controller
{
    use ApiResponse;

    // Sesuaikan dengan nilai status "disetujui" di enum BebasPustaka Anda
    private const STATUS_DISETUJUI = 'disetujui';

    public function __construct(private SuratBebasPustakaService $service)
    {
    }

    /**
     * Tampil di browser (preview PDF).
     */
    public function previewSurat(Request $request, BebasPustaka $bebasPustaka)
    {
        $path = $this->resolveFile($request, $bebasPustaka);

        if ($path instanceof JsonResponse) {
            return $path;
        }

        return response()->file($path, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Unduh paksa PDF.
     */
    public function downloadSurat(Request $request, BebasPustaka $bebasPustaka)
    {
        $path = $this->resolveFile($request, $bebasPustaka);

        if ($path instanceof JsonResponse) {
            return $path;
        }

        $namaFile = 'surat-bebas-pustaka-' . $bebasPustaka->user->nim . '.pdf';

        return response()->download($path, $namaFile, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Gambar QR (SVG). Butuh token login, jadi di frontend harus diambil
     * sebagai blob. Untuk <img src> langsung, pakai atribut qr_url (route publik).
     */
    public function qr(Request $request, BebasPustaka $bebasPustaka)
    {
        $user = $request->user();

        if ($user->hasRole('mahasiswa') && $bebasPustaka->user_id !== $user->id) {
            return $this->error('Anda tidak berhak mengakses QR ini.', null, 403);
        }

        $status = $bebasPustaka->status?->value ?? $bebasPustaka->status;

        if ($status !== self::STATUS_DISETUJUI) {
            return $this->error('QR belum tersedia, pengajuan belum disetujui.', null, 422);
        }

        if (! $bebasPustaka->qr_token) {
            $bebasPustaka = $this->service->generate($bebasPustaka);
        }

        // Baris ini yang sebelumnya hilang
        return $this->service->generateQR($bebasPustaka);
    }

    /**
     * Daftar penandatangan yang bisa dipilih (untuk dropdown di frontend).
     */
    public function daftarPenandatangan()
    {
        $daftar = collect(config('pustakawan.daftar', []))
            ->map(fn ($p, $kode) => [
                'kode'    => $kode,
                'nama'    => $p['nama'],
                'nip'     => $p['nip'],
                'jabatan' => $p['jabatan'],
            ])
            ->values();

        return $this->success('Daftar penandatangan berhasil diambil.', $daftar);
    }

    /**
     * Pustakawan memilih siapa yang menandatangani surat.
     * Kalau surat sudah terbit, dibuat ulang dengan penandatangan baru.
     */
    public function setPenandatangan(Request $request, BebasPustaka $bebasPustaka)
{
    $data = $request->validate([
        'penandatangan' => [
            'required',
            'string',
            Rule::in(array_keys(config('pustakawan.daftar', []))),
        ],
    ]);

    $bebasPustaka->update([
        'penandatangan' => $data['penandatangan'],
    ]);

    // Refresh data setelah update
    $bebasPustaka->refresh();

    $status = $bebasPustaka->status?->value ?? $bebasPustaka->status;

   if ($status === self::STATUS_DISETUJUI) {
    $bebasPustaka = $this->service->generate($bebasPustaka);
}

    return $this->success(
        'Penandatangan surat berhasil disimpan.',
        $bebasPustaka
    );
}

    /**
     * Surat bebas pustaka yang terhubung ke sebuah pengajuan clearing.
     */
    public function previewDariClearing(Request $request, PengajuanClearing $pengajuan)
    {
        $bebasPustaka = $pengajuan->bebasPustaka;

        if (! $bebasPustaka) {
            return $this->error('Pengajuan ini belum terhubung ke surat bebas pustaka.', null, 404);
        }

        $path = $this->resolveFile($request, $bebasPustaka);

        if ($path instanceof JsonResponse) {
            return $path;
        }

        return response()->file($path, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Cek akses + pastikan file ada (generate otomatis kalau belum ada).
     * Return: path file, atau JsonResponse error.
     */
    private function resolveFile(Request $request, BebasPustaka $bebasPustaka)
    {
        $user = $request->user();

        // Mahasiswa hanya boleh melihat surat miliknya sendiri
        if ($user->hasRole('mahasiswa') && $bebasPustaka->user_id !== $user->id) {
            return $this->error('Anda tidak berhak mengakses surat ini.', null, 403);
        }

        $status = $bebasPustaka->status?->value ?? $bebasPustaka->status;

        if ($status !== self::STATUS_DISETUJUI) {
            return $this->error('Surat belum tersedia, pengajuan belum disetujui.', null, 422);
        }

        if (! $bebasPustaka->file_surat
            || ! Storage::disk('public')->exists($bebasPustaka->file_surat)) {
            $bebasPustaka = $this->service->generate($bebasPustaka);
        }

        return Storage::disk('public')->path($bebasPustaka->file_surat);
    }
}
