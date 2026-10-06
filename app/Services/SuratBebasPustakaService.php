<?php

namespace App\Services;

use App\Models\BebasPustaka;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratBebasPustakaService
{
    // File: resources/views/surat/bebas-pustaka.blade.php
    private const VIEW = 'surat.bebas-pustaka';

    /**
     * Preview PDF on-the-fly (tidak disimpan ke storage).
     * Dipakai pustakawan sebelum menyetujui.
     */
    public function preview(BebasPustaka $bebasPustaka)
    {
        $bebasPustaka->load('user');

        // Template menambahkan "/IT3.F5/KP/{tahun}" setelah nomor ini
        $nomorSurat = $bebasPustaka->nomor_surat
            ?? sprintf('%03d', $bebasPustaka->id);

        // Simpan token kalau belum ada, supaya QR konsisten dengan DB
        if (! $bebasPustaka->qr_token) {
            $bebasPustaka->update([
                'qr_token' => Str::random(64),
            ]);
        }

        return $this->buatPdf($bebasPustaka, $nomorSurat, $bebasPustaka->qr_token);
    }

    /**
     * Terbitkan surat final: simpan nomor, token, dan file PDF.
     */
    public function generate(BebasPustaka $bebasPustaka)
    {
        $bebasPustaka->load('user');

        // Token dipertahankan kalau sudah ada, supaya QR di surat lama tetap berlaku
        $token = $bebasPustaka->qr_token ?: Str::random(64);
        $nomorSurat = sprintf('%03d', $bebasPustaka->id);

        $bebasPustaka->update([
            'nomor_surat' => $nomorSurat,
            'qr_token'    => $token,
        ]);

        $pdf = $this->buatPdf($bebasPustaka, $nomorSurat, $token);

        $path = "bebas-pustaka/{$bebasPustaka->id}/surat-bebas-pustaka.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        $bebasPustaka->update([
            'file_surat' => $path,
        ]);

        return $bebasPustaka->fresh();
    }

    /**
     * Gambar QR (SVG) sebagai response HTTP.
     */
    public function generateQR(BebasPustaka $bebasPustaka)
    {
        $qrSvg = $this->qrSvg($this->verifyUrl($bebasPustaka->qr_token), 300);

        return response($qrSvg, 200)->header('Content-Type', 'image/svg+xml');
    }

    // =========================================================
    // HELPER
    // =========================================================

    private function buatPdf(BebasPustaka $bebasPustaka, string $nomorSurat, string $token)
    {
        $qrCode = base64_encode($this->qrSvg($this->verifyUrl($token), 200));

        $surat = (object) [
            'nomor_surat'   => $nomorSurat,
            'nim'           => $bebasPustaka->user->nim,
            'nama'          => $bebasPustaka->user->nama,
            'departemen'    => $bebasPustaka->user->departemen,
            'tanggal_surat' => now(),
        ];

        return Pdf::loadView(self::VIEW, [
            'surat'         => $surat,
            'qrCode'        => $qrCode,
            'penandatangan' => $this->penandatangan($bebasPustaka),
        ]);
    }

    private function verifyUrl(string $token): string
    {
        // QR mengarah ke endpoint publik yang menampilkan file PDF
        return config('app.url') . '/api/surat/bebas-pustaka/file/' . $token;
    }

    private function qrSvg(string $url, int $size): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($url);
    }

    /**
     * Penandatangan dipilih pustakawan (kolom bebas_pustaka.penandatangan).
     * Kalau belum dipilih, pakai default di config/pustakawan.php.
     */
    private function penandatangan(BebasPustaka $bebasPustaka): array
    {
        $daftar = config('pustakawan.daftar', []);
        $kode = $bebasPustaka->penandatangan;

        if ($kode && isset($daftar[$kode])) {
            return $daftar[$kode];
        }

        return $daftar[config('pustakawan.default')] ?? [
            'jabatan' => 'Pustakawan',
            'nama'    => 'Wawan, S.E.',
            'nip'     => '197305182007011001',
        ];
    }
}