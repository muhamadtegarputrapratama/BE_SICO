<?php

namespace App\Services;

use App\Models\PengajuanClearing;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratClearingService
{
    public function preview(PengajuanClearing $pengajuan)
    {
        $pengajuan->load('user');

        $nomorSurat = $pengajuan->nomor_surat
            ?? 'DRAFT/SICO/' . date('Y') . '/' . $pengajuan->id;

        // Simpan token kalau belum ada, supaya QR verify konsisten dgn DB
        if (!$pengajuan->qr_token) {
            $pengajuan->update([
                'qr_token' => Str::random(64),
            ]);
        }

        $token = $pengajuan->qr_token;

        $verifyUrl = config('app.url') . '/api/surat/verify/' . $token;

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($verifyUrl);
        $qrCode = base64_encode($qrSvg);

        $surat = (object) [
            'nomor_surat' => $nomorSurat,
            'nim' => $pengajuan->user->nim,
            'nama' => $pengajuan->user->nama,
            'departemen' => $pengajuan->departemen,
            'tanggal_surat' => now(),
        ];

        $signatureLabel = 'Pejabat yang Berwenang';

        return Pdf::loadView('surat.clearing', [
            'surat' => $surat,
            'qrCode' => $qrCode,
            'signatureLabel' => $signatureLabel,
        ]);
    }

    public function generate(PengajuanClearing $pengajuan)
    {
        $pengajuan->load('user');

        $token = Str::random(64);
        $nomorSurat = 'SICO/' . date('Y') . '/' . $pengajuan->id;

        $pengajuan->update([
            'nomor_surat' => $nomorSurat,
            'qr_token' => $token,
        ]);

        $verifyUrl = config('app.url') . '/api/surat/verify/' . $token;

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($verifyUrl);
        $qrCode = base64_encode($qrSvg);

        $surat = (object) [
            'nomor_surat' => $nomorSurat,
            'nim' => $pengajuan->user->nim,
            'nama' => $pengajuan->user->nama,
            'departemen' => $pengajuan->departemen,
            'program_studi' => $pengajuan->program_studi,
            'keperluan' => 'Keperluan administrasi akademik.',
            'tanggal_surat' => now(),
        ];

        $signatureLabel = 'Pejabat yang Berwenang';

        $pdf = Pdf::loadView('surat.clearing', [
            'surat' => $surat,
            'qrCode' => $qrCode,
            'signatureLabel' => $signatureLabel,
        ]);

        $path = "clearing/{$pengajuan->id}/surat-clearing.pdf";

        Storage::disk('public')->put($path, $pdf->output());

        $pengajuan->update([
            'file_surat' => $path,
        ]);

        return $pengajuan->fresh();
    }

    public function generateQR(PengajuanClearing $pengajuan)
    {
        if (!$pengajuan->qr_token) {
            abort(404, 'QR Code belum tersedia.');
        }

        $verifyUrl = config('app.url') . '/api/surat/verify/' . $pengajuan->qr_token;

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        $qrSvg = $writer->writeString($verifyUrl);

        return response($qrSvg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}