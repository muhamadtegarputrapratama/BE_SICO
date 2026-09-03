<?php

namespace App\Services;

use App\Models\User;
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
        return [
            'role' => 'mahasiswa',
            'greeting' => 'Selamat datang, ' . $user->nama,
            'statistik' => [
                'total_pengajuan' => 0,
                'pengajuan_pending' => 0,
                'pengajuan_disetujui' => 0,
                'pengajuan_ditolak' => 0,
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
        return [
            'role' => 'admin',
            'greeting' => 'Selamat datang, ' . $user->nama,
            'statistik' => [
                'pengajuan_perlu_verifikasi' => 0,
                'total_pengajuan_diproses' => 0,
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
        return [
            'role' => 'pustakawan',
            'greeting' => 'Selamat datang, ' . $user->nama,
            'statistik' => [
                'pengajuan_perlu_dicek' => 0,
                'total_bebas_pustaka' => 0,
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
