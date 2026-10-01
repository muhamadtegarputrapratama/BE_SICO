<?php

namespace App\Services;

use App\Enums\BebasPustakaStatus;
use App\Models\BebasPustaka;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BebasPustakaService
{
    use LogsActivity;

    protected const DISK = 'local';

    public function ajukan(User $user, UploadedFile $fileSkripsi, UploadedFile $fileDistribusi): BebasPustaka
    {
        return DB::transaction(function () use ($user, $fileSkripsi, $fileDistribusi) {
            $pengajuanTerakhir = BebasPustaka::where('user_id', $user->id)
                ->whereIn('status', [
                    BebasPustakaStatus::DIAJUKAN,
                    BebasPustakaStatus::REVISI,
                    BebasPustakaStatus::DISETUJUI,
                ])
                ->lockForUpdate()
                ->latest()
                ->first();

            if ($pengajuanTerakhir) {
                if ($pengajuanTerakhir->status === BebasPustakaStatus::DISETUJUI) {
                    throw ValidationException::withMessages([
                        'bebas_pustaka' => ['Pengajuan bebas pustaka Anda sudah disetujui. Anda tidak dapat mengajukan lagi.'],
                    ]);
                }

                throw ValidationException::withMessages([
                    'bebas_pustaka' => ['Anda masih memiliki pengajuan bebas pustaka yang belum selesai.'],
                ]);
            }

            $pathSkripsi = $this->simpanFile($fileSkripsi, $user->id);
            $pathDistribusi = $this->simpanFile($fileDistribusi, $user->id);

            try {
                $bebasPustaka = BebasPustaka::create([
                    'user_id' => $user->id,
                    'status' => BebasPustakaStatus::DIAJUKAN,
                    'file_skripsi' => $pathSkripsi,
                    'file_distribusi' => $pathDistribusi,
                ]);
            } catch (\Throwable $e) {
                $this->hapusFileLama($pathSkripsi);
                $this->hapusFileLama($pathDistribusi);

                throw $e;
            }

            $this->logActivity($user, 'Mengajukan bebas pustaka');

            return $bebasPustaka;
        });
    }

    public function review(BebasPustaka $bebasPustaka, User $pustakawan, string $keputusan, ?string $catatan): BebasPustaka
    {
        if ($bebasPustaka->status !== BebasPustakaStatus::DIAJUKAN) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini sudah diproses sebelumnya']
            ]);
        }

        $status = match ($keputusan) {
            'setuju' => BebasPustakaStatus::DISETUJUI,
            'revisi' => BebasPustakaStatus::REVISI,
            'tolak' => BebasPustakaStatus::DITOLAK,
        };

        $bebasPustaka->update([
            'status' => $status,
            'catatan_revisi' => $keputusan === 'revisi' ? $catatan : null,
            'direview_oleh' => $pustakawan->id,
            'direview_at' => now(),
        ]);

        $this->logActivity($pustakawan, "Review bebas pustaka #{$bebasPustaka->id}: {$status->label()}");

        return $bebasPustaka->fresh();
    }

    public function ajukanUlang(BebasPustaka $bebasPustaka, User $mahasiswa, UploadedFile $fileSkripsi, UploadedFile $fileDistribusi): BebasPustaka
    {
        if (! in_array($bebasPustaka->status, [BebasPustakaStatus::REVISI, BebasPustakaStatus::DISETUJUI])) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini tidak dapat diajukan ulang pada status saat ini.'],
            ]);
        }

        if ($bebasPustaka->status === BebasPustakaStatus::DISETUJUI && $bebasPustaka->pengajuanClearing()->exists()) {
            throw ValidationException::withMessages([
                'bebas_pustaka' => ['Bebas pustaka ini sudah dipakai untuk pengajuan clearing dan tidak dapat diubah lagi.'],
            ]);
        }

        return DB::transaction(function () use ($bebasPustaka, $mahasiswa, $fileSkripsi, $fileDistribusi) {
            $skripsiLama = $bebasPustaka->file_skripsi;
            $distribusiLama = $bebasPustaka->file_distribusi;

            $bebasPustaka->update([
                'file_skripsi' => $this->simpanFile($fileSkripsi, $mahasiswa->id),
                'file_distribusi' => $this->simpanFile($fileDistribusi, $mahasiswa->id),
                'status' => BebasPustakaStatus::DIAJUKAN,
                'catatan_revisi' => null,
                'direview_oleh' => null,
                'direview_at' => null,
            ]);

            $this->hapusFileLama($skripsiLama);
            $this->hapusFileLama($distribusiLama);

            $this->logActivity($mahasiswa, "Mengajukan ulang bebas pustaka #{$bebasPustaka->id}");

            return $bebasPustaka->fresh();
        });
    }

    protected function simpanFile(UploadedFile $file, int $userId): string
    {
        return $file->store("bebas-pustaka/{$userId}", self::DISK)
            ?: throw ValidationException::withMessages([
                'file' => ['Gagal menyimpan file.'],
            ]);
    }

    protected function hapusFileLama(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
