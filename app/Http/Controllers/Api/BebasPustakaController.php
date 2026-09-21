<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BebasPustaka\AjukanUlangBebasPustakaRequest;
use App\Http\Requests\BebasPustaka\StoreBebasPustakaRequest;
use App\Http\Requests\ReviewRequest;
use App\Models\BebasPustaka;
use App\Services\BebasPustakaService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BebasPustakaController extends Controller
{
    use ApiResponse;

    public function __construct(protected BebasPustakaService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 15);

        $query = $user->hasAnyRole(['pustakawan', 'atasan'])
            ? BebasPustaka::with('user')->latest()
            : BebasPustaka::with('user')->where('user_id', $user->id)->latest();

        return $this->success('Data bebas pustaka berhasil diambil.', $query->paginate($perPage));
    }

    public function store(StoreBebasPustakaRequest $request): JsonResponse
    {
        $bebasPustaka = $this->service->ajukan($request->user(), $request->file('file_skripsi'));

        return $this->success('Pengajuan bebas pustaka berhasil dibuat.', $bebasPustaka, 201);
    }

    public function review(ReviewRequest $request, BebasPustaka $bebasPustaka): JsonResponse
    {
        if (! $request->user()->can('verifikasi-pustaka')) {
            return $this->error('Anda tidak memiliki akses.', null, 403);
        }

        $bebasPustaka = $this->service->review(
            $bebasPustaka,
            $request->user(),
            $request->validated('keputusan'),
            $request->validated('catatan_revisi')
        );

        return $this->success('Review bebas pustaka berhasil disimpan.', $bebasPustaka);
    }

    public function ajukanUlang(AjukanUlangBebasPustakaRequest $request, BebasPustaka $bebasPustaka): JsonResponse
    {
        if ($bebasPustaka->user_id !== $request->user()->id) {
            return $this->error('Anda tidak memiliki akses.', null, 403);
        }

        $request->validate([
            'file_skripsi' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'file_skripsi.mimes' => 'File skripsi harus berformat PDF.',
            'file_skripsi.max' => 'Ukuran file skripsi maksimal 10 MB.',
            'file_skripsi.uploaded' => 'File gagal diunggah. Ukuran melebihi batas server.',
        ]);

        $bebasPustaka = $this->service->ajukanUlang(
            $bebasPustaka,
            $request->user(),
            $request->file('file_skripsi')
        );

        return $this->success('Pengajuan bebas pustaka berhasil diajukan ulang.', $bebasPustaka);
    }

    public function previewSkripsi(Request $request, BebasPustaka $bebasPustaka)
    {
        $user = $request->user();

        $bolehAkses = $bebasPustaka->user_id === $user->id || $user->hasAnyRole(['pustakawan', 'atasan']);

        if (! $bolehAkses) {
            return $this->error('Anda tidak memiliki akses ke dokumen ini.', null, 403);
        }

        if (! $bebasPustaka->file_skripsi || ! Storage::disk('public')->exists($bebasPustaka->file_skripsi)) {
            return $this->error('File skripsi tidak ditemukan.', null, 404);
        }

        return response()->file(
            Storage::disk('public')->path($bebasPustaka->file_skripsi)
        );
    }
}
