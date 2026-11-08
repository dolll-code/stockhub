@extends('reports.pdf.layout')

@section('title', 'Laporan Pergerakan Stok — StockHub')
@section('report_title', 'LAPORAN PERGERAKAN STOK PERSAMAAN BUKU BESAR')
@section('report_subtitle', 'Rekapitulasi Riwayat Mutasi Persediaan Lengkap (Termasuk Mutasi Pembalik)')

@section('content')
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 85px;">Waktu & Tanggal</th>
                <th style="width: 80px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th style="width: 90px;">Jenis Mutasi</th>
                <th style="width: 90px;">No. Referensi</th>
                <th style="width: 55px;" class="text-right">Sebelum</th>
                <th style="width: 55px;" class="text-right">Perubahan</th>
                <th style="width: 55px;" class="text-right">Sesudah</th>
                <th style="width: 45px;">Satuan</th>
                <th style="width: 75px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                @php
                    $badgeClass = match($row->movement_type->value ?? $row->movement_type) {
                        'opening_balance' => 'badge-min',
                        'stock_in', 'stock_out_cancel' => 'badge-aman',
                        'stock_out', 'stock_in_cancel' => 'badge-habis',
                        default => '',
                    };
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-size: 7.5pt;">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                    <td class="font-mono">{{ $row->product->code }}</td>
                    <td>{{ $row->product->name }}</td>
                    <td>
                        <span class="badge {{ $badgeClass }}">{{ $row->movement_type->label() }}</span>
                    </td>
                    <td class="font-mono">{{ $row->reference_number ?? '—' }}</td>
                    <td class="text-right font-mono">{{ rtrim(rtrim(number_format((float) $row->qty_before, 3, ',', '.'), '0'), ',') }}</td>
                    <td class="text-right font-mono font-bold">
                        {{ (float) $row->qty_change > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $row->qty_change, 3, ',', '.'), '0'), ',') }}
                    </td>
                    <td class="text-right font-mono font-bold">{{ rtrim(rtrim(number_format((float) $row->qty_after, 3, ',', '.'), '0'), ',') }}</td>
                    <td>{{ $row->product->unit->symbol }}</td>
                    <td>{{ $row->user?->name ?? 'Sistem' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 15px; color: #64748b;">
                        Tidak ada catatan pergerakan stok yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
