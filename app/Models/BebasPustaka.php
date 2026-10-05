<?php

namespace App\Models;

use App\Enums\BebasPustakaStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BebasPustaka extends Model
{
    protected $table = 'bebas_pustaka';

    protected $fillable = [
        'user_id',
        'status',
        'file_skripsi',
        'file_distribusi',
        'catatan_revisi',
        'direview_oleh',
        'direview_at',
        'nomor_surat',
        'qr_token',
        'file_surat',
    ];

    /**
     * Path internal storage tidak dikirim ke JSON response.
     */
    protected $hidden = [
        'file_skripsi',
        'file_distribusi',
        'file_surat',
        'qr_token',
    ];

    /**
     * Frontend hanya menerima informasi
     * apakah file sudah tersedia atau belum.
     */
    protected $appends = [
        'ada_file_skripsi',
        'ada_file_distribusi',
        'ada_file_surat',
        'qr_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => BebasPustakaStatus::class,
            'direview_at' => 'datetime',
        ];
    }

    protected function adaFileSkripsi(): Attribute
    {
        return Attribute::get(
            fn () => !empty($this->file_skripsi)
        );
    }

    protected function adaFileDistribusi(): Attribute
    {
        return Attribute::get(
            fn () => !empty($this->file_distribusi)
        );
    }

    protected function adaFileSurat(): Attribute
    {
        return Attribute::get(
            fn () => !empty($this->file_surat)
        );
    }

    /**
     * URL gambar QR untuk <img src> di frontend (null kalau surat belum dibuat).
     */
    protected function qrUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->qr_token
                ? url('/api/surat/bebas-pustaka/qr/' . $this->qr_token)
                : null
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'direview_oleh'
        );
    }

    public function pengajuanClearing(): HasOne
    {
        return $this->hasOne(
            PengajuanClearing::class
        );
    }
}