<?php
// app/Http/Controllers/Api/LaporanController.php

namespace App\Http\Controllers\Api;

use App\Exports\PengajuanClearingExport;
use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    use ApiResponse;

    public function __construct(protected LaporanService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasRole('admin')) {
            return $this->error('Anda tidak memiliki akses.', null, 403);
        }

        $filters = $request->only(['status', 'program_studi', 'dari_tanggal', 'sampai_tanggal']);
        $perPage = $request->input('per_page', 20); // default 20, tapi bisa di-override

        return $this->success('Laporan berhasil diambil.', $this->service->query($filters)->paginate($perPage));
    }

    public function export(Request $request)
    {
        if (! $request->user()->hasRole('admin')) {
            return $this->error('Anda tidak memiliki akses.', null, 403);
        }

        $filters = $request->only(['status', 'program_studi', 'dari_tanggal', 'sampai_tanggal']);

        return Excel::download(
            new PengajuanClearingExport($filters),
            'laporan-clearing-'.now()->format('Ymd-His').'.xlsx'
        );
    }
}