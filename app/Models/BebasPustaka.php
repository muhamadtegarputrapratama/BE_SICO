<?php
namespace App\Models;

use App\Enums\BebasPustakaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BebasPustaka extends Model
{
    protected $table = 'bebas_pustaka';

    protected $fillable = [
        'user_id',
        'status',
        'catatan_revisi',
        'direview_oleh',
        'direview_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BebasPustakaStatus::class,
            'direview_at' => 'datetime',
        ];
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