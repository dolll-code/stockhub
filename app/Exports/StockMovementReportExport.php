<?php

namespace App\Exports;

use App\Models\StockMovement;
use App\Support\CsvFormulaSanitizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, StockMovement>  $movements
     */
    public function __construct(
        protected Collection $movements
    ) {}

    public function collection(): Collection
    {
        return $this->movements;
    }

    public function headings(): array
    {
        return [
            'Waktu & Tanggal',
            'Tanggal Bisnis',
            'Kode Barang',
            'Nama Barang',
            'Jenis Mutasi',
            'No. Referensi',
            'Saldo Sebelum',
            'Perubahan',
            'Saldo Sesudah',
            'Satuan',
            'Petugas',
            'Catatan',
        ];
    }

    /**
     * @param  StockMovement  $m
     * @return array<int, mixed>
     */
    public function map($m): array
    {
        return [
            $m->created_at->format('Y-m-d H:i:s'),
            $m->movement_date->format('Y-m-d'),
            CsvFormulaSanitizer::sanitize($m->product->code),
            CsvFormulaSanitizer::sanitize($m->product->name),
            $m->movement_type->label(),
            CsvFormulaSanitizer::sanitize($m->reference_number ?? '—'),
            (float) $m->qty_before,
            (float) $m->qty_change,
            (float) $m->qty_after,
            CsvFormulaSanitizer::sanitize($m->product->unit->symbol),
            CsvFormulaSanitizer::sanitize($m->user ? $m->user->name : 'Sistem'),
            CsvFormulaSanitizer::sanitize($m->notes ?? '—'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Laporan Pergerakan Stok';
    }
}
