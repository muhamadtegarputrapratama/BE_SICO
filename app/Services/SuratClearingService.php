<?php

namespace App\Services;

use App\Models\PengajuanClearing;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SuratClearingService
{
    public function generate(PengajuanClearing $pengajuan)
    {
        $token = Str::random(64);

        $nomorSurat = 'SICO/' . date('Y') . '/' . $pengajuan->id;

        $pengajuan->update([
            'nomor_surat' => $nomorSurat,
            'qr_token' => $token,
        ]);

        $verifyUrl = config('app.url') . '/api/surat/verify/' . $token;

        $qrCode = base64_encode(
            QrCode::format('svg')
                ->size(200)
                ->generate($verifyUrl)
        );

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

        Storage::disk('public')->put(
            $path,
            $pdf->output()
        );

        $pengajuan->update([
            'file_surat' => $path,
        ]);

        return $pengajuan->fresh();
    }
}
