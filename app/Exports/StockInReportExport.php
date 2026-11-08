<?php

namespace App\Exports;

use App\Models\StockInDetail;
use App\Support\CsvFormulaSanitizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockInReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, StockInDetail>  $details
     */
    public function __construct(
        protected Collection $details,
        protected bool $canViewPrice = false
    ) {}

    public function collection(): Collection
    {
        return $this->details;
    }

    public function headings(): array
    {
        $headers = [
            'No. Dokumen',
            'Tanggal',
            'Sumber',
            'Supplier',
            'Kode Barang',
            'Nama Barang',
            'Satuan',
            'Jumlah',
        ];

        if ($this->canViewPrice) {
            $headers[] = 'Harga Satuan (Rp)';
            $headers[] = 'Subtotal (Rp)';
        }

        $headers[] = 'Petugas';

        return $headers;
    }

    /**
     * @param  StockInDetail  $detail
     * @return array<int, mixed>
     */
    public function map($detail): array
    {
        $row = [
            CsvFormulaSanitizer::sanitize($detail->stockIn->number),
            $detail->stockIn->transaction_date->format('Y-m-d'),
            $detail->stockIn->source_type->label(),
            CsvFormulaSanitizer::sanitize($detail->stockIn->supplier ? $detail->stockIn->supplier->name : '—'),
            CsvFormulaSanitizer::sanitize($detail->product->code),
            CsvFormulaSanitizer::sanitize($detail->product->name),
            CsvFormulaSanitizer::sanitize($detail->product->unit->symbol),
            (float) $detail->quantity,
        ];

        if ($this->canViewPrice) {
            $row[] = (float) $detail->unit_price;
            $row[] = (float) $detail->subtotal;
        }

        $row[] = CsvFormulaSanitizer::sanitize($detail->stockIn->creator ? $detail->stockIn->creator->name : '—');

        return $row;
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
        return 'Laporan Barang Masuk';
    }
}
