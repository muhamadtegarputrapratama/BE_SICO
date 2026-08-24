<?php
// app/Services/BebasPustakaService.php
namespace App\Services;

use App\Enums\BebasPustakaStatus;
use App\Models\BebasPustaka;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BebasPustakaService
{
    use LogsActivity;

    public function ajukan(User $user): BebasPustaka
    {
        return DB::transaction(function () use ($user) {
            $pengajuanTerakhir = BebasPustaka::where('user_id', $user->id)
                ->whereIn('status', [
                    BebasPustakaStatus::DIAJUKAN,
                    BebasPustakaStatus::REVISI,
                    BebasPustakaStatus::DISETUJUI, // NEW
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

            $bebasPustaka = BebasPustaka::create([
                'user_id' => $user->id,
                'status' => BebasPustakaStatus::DIAJUKAN,
            ]);

            // $this->logActivity($user, 'Mengajukan bebas pustaka');

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

    public function ajukanUlang(BebasPustaka $bebasPustaka, User $user): BebasPustaka
    {
        if ($bebasPustaka->status !== BebasPustakaStatus::REVISI) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini tidak dalam status revisi.'],
            ]);
        }

        $bebasPustaka->update([
            'status' => BebasPustakaStatus::DIAJUKAN,
            'catatan_revisi' => null,
        ]);

        $this->logActivity($user, "Mengajukan ulang bebas pustaka #{$bebasPustaka->id}");

        return $bebasPustaka->fresh();
    }
}