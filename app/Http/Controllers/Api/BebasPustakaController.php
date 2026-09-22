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
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BebasPustakaController extends Controller
{
    use ApiResponse;

    protected const DISK = 'local';

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

        $bebasPustaka = $this->service->ajukanUlang(
            $bebasPustaka,
            $request->user(),
            $request->file('file_skripsi')
        );

        return $this->success('Pengajuan bebas pustaka berhasil diajukan ulang.', $bebasPustaka);
    }

    private function bolehAksesFile(Request $request, BebasPustaka $bebasPustaka): bool
    {
        $user = $request->user();

        return $bebasPustaka->user_id === $user->id || $user->hasAnyRole(['pustakawan', 'atasan']);
    }

    private function namaFileSkripsi(BebasPustaka $bebasPustaka): string
    {
        return 'skripsi-' . Str::slug($bebasPustaka->user?->nim ?? (string) $bebasPustaka->id) . '.pdf';
    }

    // Tampil langsung di browser (Content-Disposition: inline)
    public function previewSkripsi(Request $request, BebasPustaka $bebasPustaka): BinaryFileResponse|JsonResponse
    {
        if (! $this->bolehAksesFile($request, $bebasPustaka)) {
            return $this->error('Anda tidak memiliki akses ke dokumen ini.', null, 403);
        }

        if (! $bebasPustaka->file_skripsi || ! Storage::disk(self::DISK)->exists($bebasPustaka->file_skripsi)) {
            return $this->error('File skripsi tidak ditemukan.', null, 404);
        }

        return response()->file(
            Storage::disk(self::DISK)->path($bebasPustaka->file_skripsi),
            ['Content-Disposition' => 'inline; filename="' . $this->namaFileSkripsi($bebasPustaka) . '"']
        );
    }

    // Dipaksa terunduh sebagai file (Content-Disposition: attachment)
    public function download(Request $request, BebasPustaka $bebasPustaka): StreamedResponse|JsonResponse
    {
        if (! $this->bolehAksesFile($request, $bebasPustaka)) {
            return $this->error('Anda tidak memiliki akses ke dokumen ini.', null, 403);
        }

        if (! $bebasPustaka->file_skripsi || ! Storage::disk(self::DISK)->exists($bebasPustaka->file_skripsi)) {
            return $this->error('File skripsi tidak ditemukan.', null, 404);
        }

        return Storage::disk(self::DISK)->download($bebasPustaka->file_skripsi, $this->namaFileSkripsi($bebasPustaka));
    }
}