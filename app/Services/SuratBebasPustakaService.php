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
    public function preview(BebasPustaka $bebasPustaka)
    {
        $bebasPustaka->load('user');

        // Template menambahkan "/IT3.F5/KP/{tahun}" setelah nomor ini
        $nomorSurat = $bebasPustaka->nomor_surat
            ?? sprintf('%03d', $bebasPustaka->id);

        // Simpan token kalau belum ada, supaya QR verify konsisten dgn DB
        if (!$bebasPustaka->qr_token) {
            $bebasPustaka->update([
                'qr_token' => Str::random(64),
            ]);
        }

        $token = $bebasPustaka->qr_token;

        // QR arahkan ke endpoint file (biar konsisten sama generate())
        $verifyUrl = config('app.url') . '/api/surat/bebas-pustaka/file/' . $token;

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($verifyUrl);
        $qrCode = base64_encode($qrSvg);

        $surat = (object) [
            'nomor_surat' => $nomorSurat,
            'nim' => $bebasPustaka->user->nim,
            'nama' => $bebasPustaka->user->nama,
            'departemen' => $bebasPustaka->user->departemen,
            'tanggal_surat' => now(),
        ];

        return Pdf::loadView('surat.bebaspustaka', [
            'surat' => $surat,
            'qrCode' => $qrCode,
        ]);
    }

    public function generate(BebasPustaka $bebasPustaka)
    {
        $bebasPustaka->load('user');

        $token = Str::random(64);
        $nomorSurat = sprintf('%03d', $bebasPustaka->id);

        $bebasPustaka->update([
            'nomor_surat' => $nomorSurat,
            'qr_token' => $token,
        ]);

        // QR arahkan ke endpoint publik yang nampilin FILE PDF langsung
        $verifyUrl = config('app.url') . '/api/surat/bebas-pustaka/file/' . $token;

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($verifyUrl);
        $qrCode = base64_encode($qrSvg);

        $surat = (object) [
            'nomor_surat' => $nomorSurat,
            'nim' => $bebasPustaka->user->nim,
            'nama' => $bebasPustaka->user->nama,
            'departemen' => $bebasPustaka->user->departemen,
            'tanggal_surat' => now(),
        ];

        $pdf = Pdf::loadView('surat.bebas-pustaka', [
            'surat' => $surat,
            'qrCode' => $qrCode,
        ]);

        $path = "bebas-pustaka/{$bebasPustaka->id}/surat-bebas-pustaka.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        $bebasPustaka->update([
            'file_surat' => $path,
        ]);

        return $bebasPustaka->fresh();
    }

    public function generateQR(BebasPustaka $bebasPustaka)
    {
        $verifyUrl = config('app.url') . '/api/surat/bebas-pustaka/file/' . $bebasPustaka->qr_token;

        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($verifyUrl);

        return response($qrSvg, 200)->header('Content-Type', 'image/svg+xml');
    }
}