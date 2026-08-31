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


        $token = $pengajuan->qr_token
            ?? Str::random(64);


        $verifyUrl = config('app.url')
            . '/api/surat/verify/'
            . $token;

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


        $verifyUrl = config('app.url')
            . '/api/surat/verify/'
            . $token;


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

        // Generate PDF dari Blade
        $pdf = Pdf::loadView('surat.clearing', [
            'surat' => $surat,
            'qrCode' => $qrCode,
            'signatureLabel' => $signatureLabel,
        ]);

        // Lokasi penyimpanan surat
        $path = "clearing/{$pengajuan->id}/surat-clearing.pdf";

        // Simpan PDF
        Storage::disk('public')->put(
            $path,
            $pdf->output()
        );

        // Simpan lokasi file ke database
        $pengajuan->update([
            'file_surat' => $path,
        ]);

        // Return data terbaru
        return $pengajuan->fresh();
    }
}
