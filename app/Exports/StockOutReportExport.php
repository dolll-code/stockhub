<?php

namespace App\Exports;

use App\Models\StockOutDetail;
use App\Support\CsvFormulaSanitizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockOutReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, StockOutDetail>  $details
     */
    public function __construct(
        protected Collection $details
    ) {}

    public function collection(): Collection
    {
        return $this->details;
    }

    public function headings(): array
    {
        return [
            'No. Dokumen',
            'Tanggal',
            'Peruntukan',
            'Tujuan / Penerima',
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Jumlah',
            'Petugas',
        ];
    }

    /**
     * @param  StockOutDetail  $detail
     * @return array<int, mixed>
     */
    public function map($detail): array
    {
        return [
            CsvFormulaSanitizer::sanitize($detail->stockOut->number),
            $detail->stockOut->transaction_date->format('Y-m-d'),
            $detail->stockOut->purpose_type->label(),
            CsvFormulaSanitizer::sanitize($detail->stockOut->destination),
            CsvFormulaSanitizer::sanitize($detail->product->code),
            CsvFormulaSanitizer::sanitize($detail->product->name),
            CsvFormulaSanitizer::sanitize($detail->product->unit->symbol),
            (float) $detail->quantity,
            CsvFormulaSanitizer::sanitize($detail->stockOut->creator ? $detail->stockOut->creator->name : '—'),
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
        return 'Laporan Barang Keluar';
    }
}
