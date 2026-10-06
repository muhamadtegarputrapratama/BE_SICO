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
    // Nama view = nama file di resources/views/surat/ (tanpa .blade.php)
    // File bebaspustaka.blade.php -> 'surat.bebaspustaka'
   private const VIEW = 'surat.bebas-pustaka';
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

        return Pdf::loadView(self::VIEW, [
            'surat' => $surat,
            'qrCode' => $qrCode,
            'penandatangan' => $this->penandatangan($bebasPustaka),
        ]);
    }

    public function generate(BebasPustaka $bebasPustaka)
    {
        $bebasPustaka->load('user');

        // Token dipertahankan kalau sudah ada, supaya QR di surat lama tetap berlaku
        $token = $bebasPustaka->qr_token ?: Str::random(64);
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

        $pdf = Pdf::loadView(self::VIEW, [
            'surat' => $surat,
            'qrCode' => $qrCode,
            'penandatangan' => $this->penandatangan($bebasPustaka),
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
