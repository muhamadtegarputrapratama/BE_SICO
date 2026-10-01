<?php

namespace App\Models;

use App\Enums\BebasPustakaStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class BebasPustaka extends Model
{
    protected $table = 'bebas_pustaka';

    protected $fillable = [
        'user_id',
        'file_skripsi',
        'file_distribusi',
        'status',
        'catatan_revisi',
        'direview_oleh',
        'direview_at',
    ];

    protected $hidden = [
        'file_skripsi',
        'file_distribusi',
    ];

    protected $appends = [
        'ada_file_skripsi',
        'ada_file_distribusi',
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
        return Attribute::get(fn () => ! empty($this->file_skripsi));
    }

    protected function adaFileDistribusi(): Attribute
    {
        return Attribute::get(fn () => ! empty($this->file_distribusi));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direview_oleh');
    }

    public function pengajuanClearing(): HasOne
    {
        return $this->hasOne(PengajuanClearing::class);
    }
}
