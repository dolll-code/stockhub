@extends('reports.pdf.layout')

@section('title', 'Laporan Persediaan — StockHub')
@section('report_title', 'LAPORAN PERSEDIAAN BARANG')
@section('report_subtitle', $isAsOf ? 'Kondisi Saldo Fisik per Tanggal ' . Carbon\Carbon::parse($asOfDate)->isoFormat('D MMMM Y') : 'Kondisi Saldo Fisik Terkini')

@section('content')
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 80px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th style="width: 80px;">Kategori</th>
                <th style="width: 60px;" class="text-right">{{ $isAsOf ? 'Stok As-Of' : 'Stok Saat Ini' }}</th>
                <th style="width: 45px;">Satuan</th>
                <th style="width: 55px;" class="text-right">Stok Min</th>
                <th style="width: 60px;" class="text-center">Status</th>
                @if($canViewPrice)
                    <th style="width: 75px;" class="text-right">Harga Beli</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                @php
                    $stokVal = isset($row->as_of_stock) ? (float) $row->as_of_stock : (float) $row->current_stock;
                    $status = \App\Enums\StockStatus::fromStock($stokVal, $row->min_stock);
                    $badgeClass = match($status->value) {
                        'aman' => 'badge-aman',
                        'minimum' => 'badge-min',
                        'habis' => 'badge-habis',
                        default => '',
                    };
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $row->code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->category->name }}</td>
                    <td class="text-right font-mono font-bold">{{ rtrim(rtrim(number_format($stokVal, 3, ',', '.'), '0'), ',') }}</td>
                    <td>{{ $row->unit->symbol }}</td>
                    <td class="text-right font-mono">{{ rtrim(rtrim(number_format((float) $row->min_stock, 3, ',', '.'), '0'), ',') }}</td>
                    <td class="text-center">
                        <span class="badge {{ $badgeClass }}">{{ $status->label() }}</span>
                    </td>
                    @if($canViewPrice)
                        <td class="text-right font-mono">@currency($row->purchase_price)</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canViewPrice ? 9 : 8 }}" class="text-center" style="padding: 15px; color: #64748b;">
                        Tidak ada data persediaan barang yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
