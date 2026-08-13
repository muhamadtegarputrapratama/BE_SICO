<?php
// app/Models/PengajuanClearing.php

namespace App\Models;

use App\Enums\PengajuanClearingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanClearing extends Model
{
    protected $table = 'pengajuan_clearing';

    protected $fillable = [
        'user_id',
        'bebas_pustaka_id',
        'departemen',
        'program_studi',
        'file_ktm',
        'file_bukti_spp',
        'file_distribusi',
        'status',
        'catatan_revisi',
        'direview_admin_oleh',
        'direview_admin_at',
        'disetujui_atasan_oleh',
        'disetujui_atasan_at',
        'nomor_surat',
        'qr_token',
        'file_surat',
    ];

    protected function casts(): array
    {
        return [
            'status' => PengajuanClearingStatus::class,
            'direview_admin_at' => 'datetime',
            'disetujui_atasan_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bebasPustaka(): BelongsTo
    {
        return $this->belongsTo(BebasPustaka::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direview_admin_oleh');
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_atasan_oleh');
    }
}