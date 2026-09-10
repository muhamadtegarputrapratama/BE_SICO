<?php 

namespace App\Enums;

enum PengajuanClearingStatus: string 
{
    case DIAJUKAN = 'menunggu';
    case REVISI_ADMIN = 'revisi_admin';
    case DIVERIFIKASI_ADMIN = 'diverifikasi_admin'; //menunggu atasan menyetujui
    case DITOLAK = 'ditolak';
    case DISETUJUI = 'disetujui'; //disetujui atasan

    public function label(): string 
    {
        return match ($this) {
            self::DIAJUKAN => 'Menunggu', 
            self::REVISI_ADMIN => 'Perlu revisi admin',
            self::DIVERIFIKASI_ADMIN => 'Menunggu persetujuan atasan',
            self::DITOLAK => 'Ditolak', 
            self::DISETUJUI => 'Disetujui',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::DISETUJUI, self::DITOLAK]);
    }

}