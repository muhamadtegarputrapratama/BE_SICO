<?php

namespace App\Exports;

use App\Services\LaporanService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PengajuanClearingExport implements FromCollection, WithHeadings, WithMapping, WithDrawings, WithEvents
{
    protected $pengajuanCollection;
    protected int $rowNo = 0; // Property penampung nomor urut

    public function __construct(protected array $filters = [])
    {
    }

    public function collection()
    {
        $this->pengajuanCollection = app(LaporanService::class)->query($this->filters)->get();
        return $this->pengajuanCollection;
    }

    public function headings(): array
    {
        return [
            'No',           // Mengganti ID menjadi Nomor Urut
            'ID Pengajuan', // Kolom ID database (opsional, hapus jika tidak diperlukan)
            'Nama', 'NIM', 'Departemen',
            'Status', 'Nomor Surat',
            'Foto KTM', 'Bukti Pembayaran', 'File Distribusi', // Kolom I, J, K
            'Diverifikasi Admin', 'Disetujui Atasan', 'Catatan Revisi', 'Tanggal Diajukan',
        ];
    }

    public function map($pengajuan): array
    {
        $this->rowNo++; // Increment nomor urut 1, 2, 3...

        return [
            $this->rowNo,   // Menampilkan nomor urut baris
            $pengajuan->id, // ID asli dari database MySQL
            $pengajuan->user?->nama ?? $pengajuan->user?->name ?? '-',
            $pengajuan->user?->nim ?? '-',
            $pengajuan->departemen ?? '-',
            is_object($pengajuan->status) && method_exists($pengajuan->status, 'label')
                ? $pengajuan->status->label()
                : $pengajuan->status,
            $pengajuan->nomor_surat ?? '-',
            '', // Tempat Foto KTM (Kolom I)
            '', // Tempat Bukti Pembayaran (Kolom J)
            '', // Tempat File Distribusi (Kolom K)
            $pengajuan->admin?->nama ?? '-',
            $pengajuan->atasan?->nama ?? '-',
            $pengajuan->catatan_revisi ?? '-',
            $pengajuan->created_at ? $pengajuan->created_at->format('d-m-Y H:i') : '-',
        ];
    }

    public function drawings()
    {
        $drawings = [];
        $rowNumber = 2; // Data mulai di baris 2 (baris 1 = Headings)
        foreach ($this->pengajuanCollection as $pengajuan) {
            // KTM & SPP ada di disk 'public', File Distribusi ada di relasi bebasPustaka (disk 'local')
            $filesToEmbed = [
                'I' => ['path' => $pengajuan->file_ktm, 'disk' => 'public'],
                'J' => ['path' => $pengajuan->file_bukti_spp, 'disk' => 'public'],
                'K' => ['path' => $pengajuan->bebasPustaka?->file_distribusi, 'disk' => 'local'],
            ];
            foreach ($filesToEmbed as $column => $item) {
                $relativePath = $item['path'];
                $disk = $item['disk'];
                if (! $relativePath || ! Storage::disk($disk)->exists($relativePath)) {
                    continue;
                }
                $fullPath = Storage::disk($disk)->path($relativePath);
                // Pastikan file berupa gambar (bukan PDF) sebelum dimasukkan ke Excel
                if (file_exists($fullPath) && @getimagesize($fullPath)) {
                    $drawing = new Drawing();
                    $drawing->setName("Doc-{$pengajuan->id}-{$column}");
                    $drawing->setDescription("Berkas Pengajuan");
                    $drawing->setPath($fullPath);
                    $drawing->setHeight(45);
                    $drawing->setCoordinates($column . $rowNumber);
                    $drawing->setOffsetX(8);
                    $drawing->setOffsetY(4);
                    $drawings[] = $drawing;
                }
            }
            $rowNumber++;
        }
        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $rowCount = count($this->pengajuanCollection);

                // Set tinggi baris data agar gambar muat dengan rapi
                for ($row = 2; $row <= ($rowCount + 1); $row++) {
                    $event->sheet->getDelegate()->getRowDimension($row)->setRowHeight(40);
                }

                // Atur lebar kolom I, J, K khusus tempat gambar
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(8);  // Kolom No
                $event->sheet->getDelegate()->getColumnDimension('I')->setWidth(18); // Foto KTM
                $event->sheet->getDelegate()->getColumnDimension('J')->setWidth(18); // Bukti Pembayaran
                $event->sheet->getDelegate()->getColumnDimension('K')->setWidth(18); // File Distribusi
            },
        ];
    }
}
