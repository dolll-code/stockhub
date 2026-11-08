<?php

namespace App\Exports;

use App\Models\StockAdjustment;
use App\Support\CsvFormulaSanitizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockAdjustmentReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, StockAdjustment>  $adjustments
     */
    public function __construct(
        protected Collection $adjustments
    ) {}

    public function collection(): Collection
    {
        return $this->adjustments;
    }

    public function headings(): array
    {
        return [
            'No. Dokumen',
            'Tanggal',
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Stok Sebelum',
            'Stok Fisik',
            'Selisih',
            'Alasan',
            'Keterangan',
            'Petugas',
        ];
    }

    /**
     * @param  StockAdjustment  $adj
     * @return array<int, mixed>
     */
    public function map($adj): array
    {
        return [
            CsvFormulaSanitizer::sanitize($adj->number),
            $adj->adjustment_date->format('Y-m-d'),
            CsvFormulaSanitizer::sanitize($adj->product->code),
            CsvFormulaSanitizer::sanitize($adj->product->name),
            CsvFormulaSanitizer::sanitize($adj->product->unit->symbol),
            (float) $adj->system_qty,
            (float) $adj->physical_qty,
            (float) $adj->difference,
            $adj->reason_code->label(),
            CsvFormulaSanitizer::sanitize($adj->notes),
            CsvFormulaSanitizer::sanitize($adj->creator ? $adj->creator->name : '—'),
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
        return 'Laporan Penyesuaian Stok';
    }
}
