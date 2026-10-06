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
use Illuminate\Validation\Rule;

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
        $bebasPustaka = $this->service->ajukan(
            $request->user(),
            $request->file('file_skripsi'),
            $request->file('file_distribusi')
        );

        return $this->success('Pengajuan bebas pustaka berhasil dibuat.', $bebasPustaka, 201);
    }

    public function review(ReviewRequest $request, BebasPustaka $bebasPustaka): JsonResponse
    {
        if (! $request->user()->can('verifikasi-pustaka')) {
            return $this->error('Anda tidak memiliki akses.', null, 403);
        }

        // Penandatangan boleh dikirim langsung bersama keputusan,
        // atau sudah tersimpan sebelumnya lewat endpoint /penandatangan.
        $request->validate([
            'penandatangan' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('pustakawan.daftar', []))),
            ],
        ]);

        if ($request->filled('penandatangan')) {
            $bebasPustaka->update([
                'penandatangan' => $request->input('penandatangan'),
            ]);
        }

        // Surat tidak boleh terbit tanpa penandatangan yang dipilih
        if ($request->validated('keputusan') === 'setuju' && ! $bebasPustaka->penandatangan) {
            return $this->error(
                'Penandatangan surat belum dipilih. Pilih penandatangan terlebih dahulu.',
                null,
                422
            );
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
            $request->file('file_skripsi'),
            $request->file('file_distribusi')
        );

        return $this->success('Pengajuan bebas pustaka berhasil diajukan ulang.', $bebasPustaka);
    }

    public function previewSkripsi(Request $request, BebasPustaka $bebasPustaka)
    {
        return $this->previewFile($request, $bebasPustaka, 'file_skripsi', 'File skripsi tidak ditemukan.');
    }

    public function previewDistribusi(Request $request, BebasPustaka $bebasPustaka)
    {
        return $this->previewFile($request, $bebasPustaka, 'file_distribusi', 'File distribusi tidak ditemukan.');
    }

    public function downloadSkripsi(Request $request, BebasPustaka $bebasPustaka)
{
    return $this->downloadFile(
        $request,
        $bebasPustaka,
        'file_skripsi',
        'File skripsi tidak ditemukan.',
        'skripsi.pdf'
    );
}

public function downloadDistribusi(Request $request, BebasPustaka $bebasPustaka)
{
    return $this->downloadFile(
        $request,
        $bebasPustaka,
        'file_distribusi',
        'File distribusi tidak ditemukan.',
        'form-distribusi-skripsi.pdf'
    );
}

    protected function previewFile(Request $request, BebasPustaka $bebasPustaka, string $field, string $pesanTidakAda)
    {
        $user = $request->user();

        $bolehAkses = $bebasPustaka->user_id === $user->id || $user->hasAnyRole(['pustakawan', 'atasan']);

        if (! $bolehAkses) {
            return $this->error('Anda tidak memiliki akses ke dokumen ini.', null, 403);
        }

        if (! $bebasPustaka->{$field} || ! Storage::disk('local')->exists($bebasPustaka->{$field})) {
            return $this->error($pesanTidakAda, null, 404);
        }

        return response()->file(
            Storage::disk('local')->path($bebasPustaka->{$field})
        );
    }

    protected function downloadFile(
    Request $request,
    BebasPustaka $bebasPustaka,
    string $field,
    string $pesanTidakAda,
    string $namaFile
) {
    $user = $request->user();

    $bolehAkses =
        $bebasPustaka->user_id === $user->id ||
        $user->hasAnyRole(['pustakawan', 'atasan']);

    if (! $bolehAkses) {
        return $this->error(
            'Anda tidak memiliki akses ke dokumen ini.',
            null,
            403
        );
    }

    if (
        ! $bebasPustaka->{$field} ||
        ! Storage::disk('local')->exists($bebasPustaka->{$field})
    ) {
        return $this->error($pesanTidakAda, null, 404);
    }

    return response()->download(
        Storage::disk('local')->path($bebasPustaka->{$field}),
        $namaFile
    );
}
}
