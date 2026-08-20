<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'dibaca',
    ];

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
