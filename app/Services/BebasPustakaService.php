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

    public function ajukan(
        User $user,
        UploadedFile $fileSkripsi,
        UploadedFile $fileDistribusi
    ): BebasPustaka {
        return DB::transaction(function () use (
            $user,
            $fileSkripsi,
            $fileDistribusi
        ) {
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
                        'bebas_pustaka' => [
                            'Pengajuan bebas pustaka Anda sudah disetujui. Anda tidak dapat mengajukan lagi.'
                        ],
                    ]);
                }

                throw ValidationException::withMessages([
                    'bebas_pustaka' => [
                        'Anda masih memiliki pengajuan bebas pustaka yang belum selesai.'
                    ],
                ]);
            }

            $pathSkripsi = $this->simpanFile(
                $fileSkripsi,
                $user->id,
                'file_skripsi'
            );

            try {
                $pathDistribusi = $this->simpanFile(
                    $fileDistribusi,
                    $user->id,
                    'file_distribusi'
                );

                try {
                    $bebasPustaka = BebasPustaka::create([
                        'user_id' => $user->id,
                        'status' => BebasPustakaStatus::DIAJUKAN,
                        'file_skripsi' => $pathSkripsi,
                        'file_distribusi' => $pathDistribusi,
                    ]);
                } catch (\Throwable $e) {
                    $this->hapusFileLama($pathDistribusi);
                    $this->hapusFileLama($pathSkripsi);

                    throw $e;
                }
            } catch (\Throwable $e) {
                $this->hapusFileLama($pathSkripsi);

                throw $e;
            }

            $this->logActivity(
                $user,
                'Mengajukan bebas pustaka'
            );

            return $bebasPustaka;
        });
    }

    public function review(
        BebasPustaka $bebasPustaka,
        User $pustakawan,
        string $keputusan,
        ?string $catatan
    ): BebasPustaka {
        if ($bebasPustaka->status !== BebasPustakaStatus::DIAJUKAN) {
            throw ValidationException::withMessages([
                'status' => [
                    'Pengajuan ini sudah diproses sebelumnya'
                ]
            ]);
        }

        $status = match ($keputusan) {
            'setuju' => BebasPustakaStatus::DISETUJUI,
            'revisi' => BebasPustakaStatus::REVISI,
            'tolak' => BebasPustakaStatus::DITOLAK,
        };

        $bebasPustaka->update([
            'status' => $status,
            'catatan_revisi' => $keputusan === 'revisi'
                ? $catatan
                : null,
            'direview_oleh' => $pustakawan->id,
            'direview_at' => now(),
        ]);

        $this->logActivity(
            $pustakawan,
            "Review bebas pustaka #{$bebasPustaka->id}: {$status->label()}"
        );

        return $bebasPustaka->fresh();
    }

    public function ajukanUlang(
        BebasPustaka $bebasPustaka,
        User $mahasiswa,
        UploadedFile $fileSkripsi,
        UploadedFile $fileDistribusi
    ): BebasPustaka {
        if (!in_array($bebasPustaka->status, [
            BebasPustakaStatus::REVISI,
            BebasPustakaStatus::DISETUJUI
        ])) {
            throw ValidationException::withMessages([
                'status' => [
                    'Pengajuan ini tidak dapat diajukan ulang pada status saat ini.'
                ],
            ]);
        }

        // Cegah perubahan jika sudah dipakai untuk clearing
        if (
            $bebasPustaka->status === BebasPustakaStatus::DISETUJUI
            && $bebasPustaka->pengajuanClearing()->exists()
        ) {
            throw ValidationException::withMessages([
                'bebas_pustaka' => [
                    'Bebas pustaka ini sudah dipakai untuk pengajuan clearing dan tidak dapat diubah lagi.'
                ],
            ]);
        }

        return DB::transaction(function () use (
            $bebasPustaka,
            $mahasiswa,
            $fileSkripsi,
            $fileDistribusi
        ) {
            $fileSkripsiLama = $bebasPustaka->file_skripsi;
            $fileDistribusiLama = $bebasPustaka->file_distribusi;

            $pathSkripsiBaru = $this->simpanFile(
                $fileSkripsi,
                $mahasiswa->id,
                'file_skripsi'
            );

            try {
                $pathDistribusiBaru = $this->simpanFile(
                    $fileDistribusi,
                    $mahasiswa->id,
                    'file_distribusi'
                );

                try {
                    $bebasPustaka->update([
                        'file_skripsi' => $pathSkripsiBaru,
                        'file_distribusi' => $pathDistribusiBaru,
                        'status' => BebasPustakaStatus::DIAJUKAN,
                        'catatan_revisi' => null,
                        'direview_oleh' => null,
                        'direview_at' => null,
                    ]);
                } catch (\Throwable $e) {
                    $this->hapusFileLama($pathDistribusiBaru);
                    $this->hapusFileLama($pathSkripsiBaru);

                    throw $e;
                }
            } catch (\Throwable $e) {
                $this->hapusFileLama($pathSkripsiBaru);

                throw $e;
            }

            // Hapus file lama setelah update berhasil
            $this->hapusFileLama($fileSkripsiLama);
            $this->hapusFileLama($fileDistribusiLama);

            $this->logActivity(
                $mahasiswa,
                "Mengajukan ulang file bebas pustaka #{$bebasPustaka->id}"
            );

            return $bebasPustaka->fresh();
        });
    }

    protected function simpanFile(
        UploadedFile $file,
        int $userId,
        string $jenis
    ): string {
        $path = $file->store(
            "bebas-pustaka/{$userId}/{$jenis}",
            self::DISK
        );

        if (!$path) {
            $field = $jenis === 'file_distribusi'
                ? 'file_distribusi'
                : 'file_skripsi';

            throw ValidationException::withMessages([
                $field => [
                    "Gagal menyimpan {$field}."
                ],
            ]);
        }

        return $path;
    }

    protected function hapusFileLama(?string $path): void
    {
        if (
            $path
            && Storage::disk(self::DISK)->exists($path)
        ) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
