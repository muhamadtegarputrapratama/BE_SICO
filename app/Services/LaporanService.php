<?php
// app/Services/LaporanService.php

namespace App\Services;

use App\Models\PengajuanClearing;
use Illuminate\Database\Eloquent\Builder;

class LaporanService
{
    public function query(array $filters = []): Builder
    {
        return PengajuanClearing::query()
            ->with(['user', 'admin', 'atasan', 'bebasPustaka'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters[''] ?? null, fn ($q, $prodi) => $q->where('', $prodi))
            ->when($filters['dari_tanggal'] ?? null, fn ($q, $tgl) => $q->whereDate('created_at', '>=', $tgl))
            ->when($filters['sampai_tanggal'] ?? null, fn ($q, $tgl) => $q->whereDate('created_at', '<=', $tgl))
            ->latest();
    }
}
