@extends('reports.pdf.layout')

@section('title', 'Laporan Barang Masuk — StockHub')
@section('report_title', 'LAPORAN TRANSAKSI BARANG MASUK (CONFIRMED)')
@section('report_subtitle', 'Rekapitulasi Penerimaan Barang Persediaan Terkonfirmasi')

@section('content')
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 90px;">No. Dokumen</th>
                <th style="width: 65px;">Tanggal</th>
                <th style="width: 70px;">Sumber</th>
                <th>Supplier</th>
                <th style="width: 80px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th style="width: 55px;" class="text-right">Jumlah</th>
                <th style="width: 45px;">Satuan</th>
                @if($canViewPrice)
                    <th style="width: 75px;" class="text-right">Harga Satuan</th>
                    <th style="width: 80px;" class="text-right">Subtotal</th>
                @endif
                <th style="width: 75px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $row->stockIn->number }}</td>
                    <td>{{ $row->stockIn->transaction_date->format('d/m/Y') }}</td>
                    <td>{{ $row->stockIn->source_type->label() }}</td>
                    <td>{{ $row->stockIn->supplier?->name ?? '—' }}</td>
                    <td class="font-mono">{{ $row->product->code }}</td>
                    <td>{{ $row->product->name }}</td>
                    <td class="text-right font-mono font-bold">{{ rtrim(rtrim(number_format((float) $row->quantity, 3, ',', '.'), '0'), ',') }}</td>
                    <td>{{ $row->product->unit->symbol }}</td>
                    @if($canViewPrice)
                        <td class="text-right font-mono">@currency($row->unit_price)</td>
                        <td class="text-right font-mono">@currency($row->subtotal)</td>
                    @endif
                    <td>{{ $row->stockIn->creator?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canViewPrice ? 12 : 10 }}" class="text-center" style="padding: 15px; color: #64748b;">
                        Tidak ada data transaksi barang masuk confirmed yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
