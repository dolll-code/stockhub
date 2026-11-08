@extends('reports.pdf.layout')

@section('title', 'Laporan Barang Keluar — StockHub')
@section('report_title', 'LAPORAN TRANSAKSI BARANG KELUAR (CONFIRMED)')
@section('report_subtitle', 'Rekapitulasi Pengeluaran Barang Persediaan Terkonfirmasi')

@section('content')
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 95px;">No. Dokumen</th>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 80px;">Peruntukan</th>
                <th>Tujuan / Penerima</th>
                <th style="width: 85px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th style="width: 60px;" class="text-right">Jumlah</th>
                <th style="width: 45px;">Satuan</th>
                <th style="width: 80px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $row->stockOut->number }}</td>
                    <td>{{ $row->stockOut->transaction_date->format('d/m/Y') }}</td>
                    <td>{{ $row->stockOut->purpose_type->label() }}</td>
                    <td>{{ $row->stockOut->destination }}</td>
                    <td class="font-mono">{{ $row->product->code }}</td>
                    <td>{{ $row->product->name }}</td>
                    <td class="text-right font-mono font-bold">{{ rtrim(rtrim(number_format((float) $row->quantity, 3, ',', '.'), '0'), ',') }}</td>
                    <td>{{ $row->product->unit->symbol }}</td>
                    <td>{{ $row->stockOut->creator?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px; color: #64748b;">
                        Tidak ada data transaksi barang keluar confirmed yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
