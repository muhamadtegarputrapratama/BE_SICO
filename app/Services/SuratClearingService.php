<?php

namespace App\Services;

use App\Models\PengajuanClearing;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class SuratClearingService
{
    public function generate(PengajuanClearing $pengajuan): PengajuanClearing
    {
        $nomorSurat = $this->buatNomorSurat($pengajuan);
        $qrToken = Str::uuid()->toString();

        // URL Verifikasi yang dituju saat QR Code di-scan HP
        $verifikasiUrl = route('surat.verify', ['token' => $qrToken]);

        // Folder sementara untuk simpan gambar QR Code
        $qrDirectory = storage_path('app/qr-tmp');
        if (! is_dir($qrDirectory)) {
            mkdir($qrDirectory, 0755, true);
        }

        $qrPngPath = "{$qrDirectory}/{$qrToken}.png";

        // 1. Generate QR Code Berfungsi
        $qrCode = new QrCode(data: $verifikasiUrl, size: 200); 
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $result->saveToFile($qrPngPath);

        // 2. Olah Master Template Word
        $docxRelativePath = $this->buatDokumenDariTemplate($pengajuan, $nomorSurat, $qrPngPath);

        // 3. Hapus temporary file QR Code setelah ditempel ke DOCX
        if (file_exists($qrPngPath)) {
            @unlink($qrPngPath);
        }

        // 4. Update Database Pengajuan
        $pengajuan->update([
            'nomor_surat' => $nomorSurat,
            'qr_token'    => $qrToken,
            'file_surat'  => $docxRelativePath,
        ]);

        return $pengajuan->fresh();
    }

    protected function buatNomorSurat(PengajuanClearing $pengajuan): string
    {
        if (! empty($pengajuan->nomor_surat)) {
            return $pengajuan->nomor_surat;
        }

        $tahun = now()->format('Y');

        $lastNomor = PengajuanClearing::whereYear('created_at', now()->year)
            ->whereNotNull('nomor_surat')
            ->orderBy('id', 'desc')
            ->value('nomor_surat');

        $urutan = 1;

        if ($lastNomor) {
            $parts = explode('/', $lastNomor);
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $urutan = ((int) $parts[0]) + 1;
            }
        }

        return sprintf('%04d/CLR/%s', $urutan, $tahun);
    }

    protected function buatDokumenDariTemplate(PengajuanClearing $pengajuan, string $nomorSurat, string $qrPngPath): string
    {
        $user = $pengajuan->user;

        // Path lokasi master template docx yang baru kamu edit
        $templatePath = storage_path('app/templates/Kop_FAHUTAN_Digital_Indonesia.docx');
        
        // Fallback jika file disimpan di folder public/templates/
        if (! file_exists($templatePath)) {
            $templatePath = public_path('templates/Kop_FAHUTAN_Digital_Indonesia.docx');
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        // Replace penanda variabel teks ${...}
        $templateProcessor->setValue('nomor_surat', $nomorSurat);
        $templateProcessor->setValue('nim', $user->nim ?? '-');
        $templateProcessor->setValue('nama', $user->nama ?? $user->name ?? '-');
        $templateProcessor->setValue('departemen', $pengajuan->departemen ?? '-');
        
        // Format Tanggal Bahasa Indonesia (misal: 13 Agustus 2026)
        $tanggalSurat = now()->locale('id')->translatedFormat('d F Y');
        $templateProcessor->setValue('tanggal_surat', $tanggalSurat);

        // Replace penanda gambar QR Code ${qr_code}
        if (file_exists($qrPngPath) && filesize($qrPngPath) > 0) {
            $templateProcessor->setImageValue('qr_code', [
                'path'   => $qrPngPath,
                'width'  => 85,
                'height' => 85,
            ]);
        } else {
            $templateProcessor->setValue('qr_code', '');
        }

        // Tentukan lokasi simpan file hasil ekspor ke folder private
        $cleanNomorSurat = str_replace('/', '_', $nomorSurat);
        $fileName = "{$pengajuan->id}_{$cleanNomorSurat}.docx";

        $fullPath = storage_path('app/private/surat');
        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $savePath = "{$fullPath}/{$fileName}";

        // Simpan file baru
        $templateProcessor->saveAs($savePath);

        return "surat/{$fileName}";
    }
}