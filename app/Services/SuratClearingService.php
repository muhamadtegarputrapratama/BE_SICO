<?php

namespace App\Services;

use App\Models\PengajuanClearing;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
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

        // Folder sementara simpan gambar QR Code
        $qrDirectory = storage_path('app/qr-tmp');
        if (! is_dir($qrDirectory)) {
            mkdir($qrDirectory, 0755, true);
        }

        $qrPngPath = "{$qrDirectory}/{$qrToken}.png";

        // Generate QR Code Berfungsi
        $qrCode = new QrCode(data: $verifikasiUrl, size: 200); 
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $result->saveToFile($qrPngPath);

        // Buat Dokumen Word sesuai layout fisik IPB
        $docxRelativePath = $this->buatDokumen($pengajuan, $nomorSurat, $qrPngPath);

        // Hapus file temporary QR Code
        if (file_exists($qrPngPath)) {
            @unlink($qrPngPath);
        }

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

    protected function buatDokumen(PengajuanClearing $pengajuan, string $nomorSurat, string $qrPngPath): string
    {
        $user = $pengajuan->user;

        $phpWord = new PhpWord();
        
        // Atur Margin Halaman (Potret)
        $section = $phpWord->addSection([
            'marginTop'    => 1200,
            'marginBottom' => 1200,
            'marginLeft'   => 1440,
            'marginRight'  => 1440,
        ]);

        // ==========================================
        // 1. KOP SURAT (Tabel 2 Kolom)
        // ==========================================
        $kopTable = $section->addTable([
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin'  => 0,
        ]);

        $kopTable->addRow();

        // Kolom Kiri: Logo IPB + Nama Instansi
        $cellKopKiri = $kopTable->addCell(5500);
        $logoPath = public_path('images/logo-ipb.png');

        if (file_exists($logoPath)) {
            // Nested Table untuk menyandingkan Logo (kiri) dan Teks Instansi (sebelah logo)
            $innerTable = $cellKopKiri->addTable([
                'borderSize'  => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin'  => 0,
            ]);
            $innerTable->addRow();

            // Cell Logo
            $innerTable->addCell(800)->addImage($logoPath, [
                'width'     => 45,
                'height'    => 45,
                'alignment' => Jc::LEFT,
            ]);

            // Cell Teks Instansi
            $cellText = $innerTable->addCell(4700);
            $cellText->addText('KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI', ['size' => 7, 'bold' => true]);
            $cellText->addText('INSTITUT PERTANIAN BOGOR', ['bold' => true, 'size' => 11]);
            $cellText->addText('FAKULTAS KEHUTANAN DAN LINGKUNGAN', ['bold' => true, 'size' => 9]);
        } else {
            // Fallback jika logo belum di-upload ke public/images/logo-ipb.png
            $cellKopKiri->addText('KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI', ['size' => 7, 'bold' => true]);
            $cellKopKiri->addText('INSTITUT PERTANIAN BOGOR', ['bold' => true, 'size' => 11]);
            $cellKopKiri->addText('FAKULTAS KEHUTANAN DAN LINGKUNGAN', ['bold' => true, 'size' => 9]);
        }

        // Kolom Kanan: Alamat Kampus & Kontak (Rata Kanan)
        $cellKopKanan = $kopTable->addCell(3500);
        $cellKopKanan->addText('Kampus IPB Dramaga, Bogor 16680', ['size' => 8], ['alignment' => Jc::RIGHT]);
        $cellKopKanan->addText('Telepon (0251) 8621677', ['size' => 8], ['alignment' => Jc::RIGHT]);
        $cellKopKanan->addText('fahutan@apps.ipb.ac.id', ['size' => 8], ['alignment' => Jc::RIGHT]);
        $cellKopKanan->addText('fahutan.ipb.ac.id', ['size' => 8], ['alignment' => Jc::RIGHT]);

        // Garis Pemisah Kop Surat (Bottom Border)
        $section->addTextBreak(1, ['borderBottomSize' => 12, 'borderBottomColor' => '000000']);
        $section->addTextBreak(1);

        // ==========================================
        // 2. JUDUL SURAT
        // ==========================================
        $section->addText('SURAT KETERANGAN', ['bold' => true, 'underline' => 'single', 'size' => 11], ['alignment' => Jc::CENTER]);
        if (! empty($nomorSurat)) {
            $section->addText("Nomor: {$nomorSurat}", ['size' => 10], ['alignment' => Jc::CENTER]);
        }
        $section->addTextBreak(2);

        // ==========================================
        // 3. ISI SURAT & DATA MAHASISWA
        // ==========================================
        $section->addText('Yang bertanda tangan di bawah ini menerangkan bahwa', ['size' => 11]);
        $section->addTextBreak(1);

        // Tabel Bio Data Mahasiswa
        $tableStyle = [
            'borderSize'     => 0,
            'borderColor'    => 'FFFFFF',
            'cellMarginLeft' => 200,
        ];
        $table = $section->addTable($tableStyle);

        // Baris NIM
        $table->addRow();
        $table->addCell(2000)->addText('NIM', ['size' => 11]);
        $table->addCell(300)->addText(':', ['size' => 11]);
        $table->addCell(6000)->addText($user->nim ?? '-', ['size' => 11]);

        // Baris Nama
        $table->addRow();
        $table->addCell(2000)->addText('Nama', ['size' => 11]);
        $table->addCell(300)->addText(':', ['size' => 11]);
        $table->addCell(6000)->addText($user->nama ?? $user->name ?? '-', ['size' => 11]);

        // Baris Departemen & Fakultas (Disatukan dalam 1 cell)
        $table->addRow();
        $table->addCell(2000)->addText('Departemen', ['size' => 11]);
        $table->addCell(300)->addText(':', ['size' => 11]);
        $cellDept = $table->addCell(6000);
        $cellDept->addText($pengajuan->departemen ?? '-', ['size' => 11]);
        $cellDept->addText('Fakultas Kehutanan dan Lingkungan IPB', ['size' => 11]);

        $section->addTextBreak(2);
        $section->addText('Sudah tidak lagi mempunyai pinjaman buku, majalah, uang, dll.', ['size' => 11]);
        $section->addTextBreak(3);

        // ==========================================
        // 4. TANDA TANGAN & QR CODE (Rata Kanan)
        // ==========================================
        $ttdTable = $section->addTable([
            'borderSize'  => 0,
            'borderColor' => 'FFFFFF',
        ]);
        
        $ttdTable->addRow();
        $ttdTable->addCell(4500); // Spacer Kiri
        
        $cellKanan = $ttdTable->addCell(4500);
        
        // Tanggal Format Indonesia (contoh: Bogor, 20 Juli 2026)
        $tanggalSurat = now()->locale('id')->translatedFormat('d F Y');
        $cellKanan->addText('Bogor, ' . $tanggalSurat, ['size' => 11]);
        $cellKanan->addText('Kabag TU', ['size' => 11]);

        // Sisipkan Gambar QR Code
        if (file_exists($qrPngPath) && filesize($qrPngPath) > 0) {
            $cellKanan->addImage($qrPngPath, [
                'width'     => 80,
                'height'    => 80,
                'alignment' => Jc::LEFT,
            ]);
        }

        $cellKanan->addText('Pungki Prayughi, S.Kom, M.Kom', ['bold' => true, 'size' => 11]);
        $cellKanan->addText('NIP. 197403092009101001', ['size' => 10]);

        // ==========================================
        // 5. SIMPAN FILE KE STORAGE PRIVATE
        // ==========================================
        $cleanNomorSurat = str_replace('/', '_', $nomorSurat);
        $fileName = "{$pengajuan->id}_{$cleanNomorSurat}.docx";

        // Menyimpan file langsung ke storage/app/private/surat
        $fullPath = storage_path('app/private/surat');
        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $savePath = "{$fullPath}/{$fileName}";

        $xmlWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $xmlWriter->save($savePath);

        return "surat/{$fileName}";
    }
}