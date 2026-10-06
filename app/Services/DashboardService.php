<?php

namespace App\Services;

use App\Models\User;
use App\Enums\BebasPustakaStatus;
use App\Enums\PengajuanClearingStatus;
use App\Models\BebasPustaka;
use App\Models\PengajuanClearing;

class DashboardService
{
    public function getDashboardData(User $user): array
    {
        $role = $user->getRoleNames()->first();

        return match ($role) {
            'mahasiswa' => $this->mahasiswaDashboard($user),
            'admin' => $this->adminDashboard($user),
            'atasan' => $this->atasanDashboard($user),
            'pustakawan' => $this->pustakawanDashboard($user),
            default => $this->defaultDashboard($user),
        };
    }

     protected function mahasiswaDashboard(User $user): array
    {
        $total = PengajuanClearing::where('user_id', $user->id)->count();
        $pending = PengajuanClearing::where('user_id', $user->id)
            ->whereIn('status', [
                PengajuanClearingStatus::DIAJUKAN,
                PengajuanClearingStatus::DIVERIFIKASI_ADMIN,
                PengajuanClearingStatus::REVISI_ADMIN,
            ])->count();
        $disetujui = PengajuanClearing::where('user_id', $user->id)
            ->where('status', PengajuanClearingStatus::DISETUJUI)->count();
        $ditolak = PengajuanClearing::where('user_id', $user->id)
            ->where('status', PengajuanClearingStatus::DITOLAK)->count();
        return [
            'role' => 'mahasiswa',
            'greeting' => 'Selamat datang, ' . $user->nama,
            'statistik' => [
                'total_pengajuan' => $total,
                'pengajuan_pending' => $pending,
                'pengajuan_disetujui' => $disetujui,
                'pengajuan_ditolak' => $ditolak,
            ],
            'menu' => [
                'buat_pengajuan',
                'riwayat_pengajuan',
                'unduh_surat',
            ],
        ];
    }

     protected function adminDashboard(User $user): array
    {
        $perluVerifikasi = PengajuanClearing::where('status', PengajuanClearingStatus::DIAJUKAN)->count();
        $totalDiproses = PengajuanClearing::whereIn('status', [
            PengajuanClearingStatus::DIVERIFIKASI_ADMIN,
            PengajuanClearingStatus::DISETUJUI,
            PengajuanClearingStatus::DITOLAK,
        ])->count();
        return [
            'role' => 'admin',
            'greeting' => 'Selamat datang, ' . $user->nama,
            'statistik' => [
                'pengajuan_perlu_verifikasi' => $perluVerifikasi,
                'total_pengajuan_diproses' => $totalDiproses,
            ],
            'menu' => [
                'verifikasi_pengajuan',
                'kelola_user',
                'kelola_jenis_surat',
            ],
        ];
    }

   protected function atasanDashboard(User $user): array
{
    $total = PengajuanClearing::count();
    $disetujui = PengajuanClearing::where('status', 'disetujui')->count();
    $ditolak = PengajuanClearing::where('status', 'ditolak')->count();

    return [
        'role' => 'atasan',
        'greeting' => 'Selamat datang, ' . $user->nama,
        'statistik' => [
            'total_pengajuan' => $total,
            'sudah_disetujui' => $disetujui,
            'sudah_ditolak' => $ditolak,
        ],
        'menu' => [
            'tanda_tangan_surat',
            'riwayat_persetujuan',
        ],
    ];
}

     protected function pustakawanDashboard(User $user): array
    {
        $perluDicek = BebasPustaka::where('status', BebasPustakaStatus::DIAJUKAN)->count();
        $totalBebasPustaka = BebasPustaka::count();
        return [
            'role' => 'pustakawan',
            'greeting' => 'Selamat datang, ' . $user->nama,
            'statistik' => [
                'pengajuan_perlu_dicek' => $perluDicek,
                'total_bebas_pustaka' => $totalBebasPustaka,
            ],
            'menu' => [
                'cek_pinjaman_buku',
                'riwayat_verifikasi',
            ],
        ];
    }

    protected function defaultDashboard(User $user): array
    {
        return [
            'role' => null,
            'greeting' => 'Selamat datang, ' . $user->nama,
            'statistik' => [],
            'menu' => [],
        ];
    }
}
