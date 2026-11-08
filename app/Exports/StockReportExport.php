<?php

namespace App\Exports;

use App\Enums\StockStatus;
use App\Models\Product;
use App\Support\CsvFormulaSanitizer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, Product>  $products
     */
    public function __construct(
        protected Collection $products,
        protected bool $canViewPrice = false,
        protected bool $isAsOf = false
    ) {}

    public function collection(): Collection
    {
        return $this->products;
    }

    public function headings(): array
    {
        $headers = [
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            $this->isAsOf ? 'Stok per Tanggal' : 'Stok Saat Ini',
            'Satuan',
            'Stok Minimum',
            'Status Stok',
            'Status Keaktifan',
        ];

        if ($this->canViewPrice) {
            $headers[] = 'Harga Beli (Rp)';
        }

        return $headers;
    }

    /**
     * @param  Product  $product
     * @return array<int, mixed>
     */
    public function map($product): array
    {
        $stock = isset($product->as_of_stock) ? (float) $product->as_of_stock : (float) $product->current_stock;
        $status = StockStatus::fromStock($stock, $product->min_stock);

        $row = [
            CsvFormulaSanitizer::sanitize($product->code),
            CsvFormulaSanitizer::sanitize($product->name),
            CsvFormulaSanitizer::sanitize($product->category->name),
            $stock,
            CsvFormulaSanitizer::sanitize($product->unit->symbol),
            (float) $product->min_stock,
            $status->label(),
            $product->is_active ? 'Aktif' : 'Nonaktif',
        ];

        if ($this->canViewPrice) {
            $row[] = (float) $product->purchase_price;
        }

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
        return 'Laporan Persediaan';
    }
}
