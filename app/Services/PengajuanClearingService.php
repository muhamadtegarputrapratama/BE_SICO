<?php
// app/Services/PengajuanClearingService.php

namespace App\Services;

use App\Enums\BebasPustakaStatus;
use App\Enums\PengajuanClearingStatus;
use App\Models\BebasPustaka;
use App\Models\PengajuanClearing;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PengajuanClearingService
{
    use LogsActivity;

    public function __construct(protected SuratClearingService $suratService)
    {
    }

    public function ajukan(User $user, array $data): PengajuanClearing
    {
        $bebasPustaka = BebasPustaka::where('user_id', $user->id)
            ->where('status', BebasPustakaStatus::DISETUJUI)
            ->whereDoesntHave('pengajuanClearing')
            ->latest()
            ->first();

        if (! $bebasPustaka) {
            throw ValidationException::withMessages([
                'bebas_pustaka' => ['Anda belum memiliki bebas pustaka yang disetujui, atau sudah pernah dipakai untuk pengajuan clearing.'],
            ]);
        }

        $pengajuan = PengajuanClearing::create([
            'user_id' => $user->id,
            'bebas_pustaka_id' => $bebasPustaka->id,
            'departemen' => $data['departemen'],
            'program_studi' => $data['program_studi'],
            'file_ktm' => $this->simpanFile($data['file_ktm'], $user->id, 'ktm'),
            'file_bukti_spp' => $this->simpanFile($data['file_bukti_spp'], $user->id, 'spp'),
            'file_distribusi' => $this->simpanFile($data['file_distribusi'], $user->id, 'distribusi'),
            'status' => PengajuanClearingStatus::DIAJUKAN,
        ]);

        $this->logActivity($user, "Mengajukan pengajuan clearing #{$pengajuan->id}");

        return $pengajuan;
    }

    public function ajukanUlang(PengajuanClearing $pengajuan, User $user, array $data): PengajuanClearing
    {
        if ($pengajuan->status !== PengajuanClearingStatus::REVISI_ADMIN) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini tidak dalam status revisi.'],
            ]);
        }

        $payload = [
            'departemen' => $data['departemen'] ?? $pengajuan->departemen,
            'program_studi' => $data['program_studi'] ?? $pengajuan->program_studi,
            'status' => PengajuanClearingStatus::DIAJUKAN,
            'catatan_revisi' => null,
        ];

        foreach (['file_ktm', 'file_bukti_spp', 'file_distribusi'] as $field) {
            if (isset($data[$field])) {
                $this->hapusFileLama($pengajuan->{$field});
                $label = str_replace('file_', '', $field);
                $payload[$field] = $this->simpanFile($data[$field], $user->id, $label);
            }
        }

        $pengajuan->update($payload);

        $this->logActivity($user, "Mengajukan ulang pengajuan clearing #{$pengajuan->id}");

        return $pengajuan->fresh();
    }

    public function reviewAdmin(PengajuanClearing $pengajuan, User $admin, string $keputusan, ?string $catatan): PengajuanClearing
    {
        if ($pengajuan->status !== PengajuanClearingStatus::DIAJUKAN) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini sudah diproses sebelumnya']
            ]);
        }

        $status = match ($keputusan) {
            'setuju' => PengajuanClearingStatus::DIVERIFIKASI_ADMIN,
            'revisi' => PengajuanClearingStatus::REVISI_ADMIN,
            'tolak' => PengajuanClearingStatus::DITOLAK,
        };

        $pengajuan->update([
            'status' => $status,
            'catatan_revisi' => $keputusan === 'revisi' ? $catatan : null,
            'direview_admin_oleh' => $admin->id,
            'direview_admin_at' => now(),
        ]);

        $this->logActivity($admin, "Review admin pengajuan clearing #{$pengajuan->id}: {$status->label()}");

        return $pengajuan->fresh();
    }


   public function reviewAtasan(
    PengajuanClearing $pengajuan,
    User $atasan,
    string $keputusan
): PengajuanClearing {

    $statusAktual = $pengajuan->status instanceof \BackedEnum
        ? $pengajuan->status->value
        : (string) $pengajuan->status;

    $statusDiharapkan = PengajuanClearingStatus::DIVERIFIKASI_ADMIN->value;

    if ($statusAktual !== $statusDiharapkan) {
        throw ValidationException::withMessages([
            'status' => [
                "Status saat ini adalah '{$statusAktual}', padahal yang dibutuhkan adalah '{$statusDiharapkan}'."
            ],
        ]);
    }

    // Kalau ditolak
    if ($keputusan === 'tolak') {
        $pengajuan->update([
            'status' => PengajuanClearingStatus::DITOLAK,
            'disetujui_atasan_oleh' => $atasan->id,
            'disetujui_atasan_at' => now(),
        ]);

        $this->logActivity(
            $atasan,
            "Menolak pengajuan clearing #{$pengajuan->id}"
        );

        return $pengajuan->fresh();
    }

    // Kalau disetujui
    $pengajuan->update([
        'status' => PengajuanClearingStatus::DISETUJUI,
        'disetujui_atasan_oleh' => $atasan->id,
        'disetujui_atasan_at' => now(),
    ]);

    // Generate surat PDF + QR
    $this->suratService->generate(
        $pengajuan->fresh()
    );

    $this->logActivity(
        $atasan,
        "Menyetujui pengajuan clearing #{$pengajuan->id}, surat diterbitkan"
    );

    return $pengajuan->fresh();
}

   protected function simpanFile(UploadedFile $file, int $userId, string $label): string
    {
        // Ganti 'local' menjadi 'public'
        return $file->store("clearing/{$userId}", 'public') ?: throw ValidationException::withMessages([
            'file' => ["Gagal menyimpan file {$label}."],
        ]);
    }

    protected function hapusFileLama(?string $path): void
    {
        // Ganti 'local' menjadi 'public'
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
