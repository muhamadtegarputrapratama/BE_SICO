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
        'catatan_revisi',
        'direview_oleh',
        'direview_at',
    ];

    // Path internal storage tidak ikut terkirim di JSON
    protected $hidden = [
        'file_skripsi',
    ];

    // Sebagai gantinya, frontend cukup tahu apakah file sudah diunggah
    protected $appends = [
        'ada_file_skripsi',
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