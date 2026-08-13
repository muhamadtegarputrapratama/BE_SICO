<?php 

namespace App\Enums;

enum BebasPustakaStatus: string 
{
    case DIAJUKAN = 'menunggu';
    case DISETUJUI = 'disetujui';
    case REVISI = 'revisi';
    case DITOLAK = 'ditolak';

    public function label(): string 
    {
        return match ($this) {
            self::DIAJUKAN => 'Menunggu', 
            self::DISETUJUI => 'Disetujui',
            self::REVISI => 'Revisi',
            self::DITOLAK => 'Ditolak',
        };
    }

}