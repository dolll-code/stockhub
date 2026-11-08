@extends('reports.pdf.layout')

@section('title', 'Laporan Penyesuaian Stok — StockHub')
@section('report_title', 'LAPORAN PENYESUAIAN STOK OPNAME')
@section('report_subtitle', 'Rekapitulasi Koreksi dan Selisih Stok Opname Fisik Gudang')

@section('content')
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 95px;">No. Dokumen</th>
                <th style="width: 65px;">Tanggal</th>
                <th style="width: 80px;">Kode Barang</th>
                <th>Nama Barang</th>
                <th style="width: 45px;">Satuan</th>
                <th style="width: 55px;" class="text-right">Stok Sistem</th>
                <th style="width: 55px;" class="text-right">Stok Fisik</th>
                <th style="width: 55px;" class="text-right">Selisih</th>
                <th style="width: 80px;">Alasan</th>
                <th>Keterangan</th>
                <th style="width: 75px;">Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $row->number }}</td>
                    <td>{{ $row->adjustment_date->format('d/m/Y') }}</td>
                    <td class="font-mono">{{ $row->product->code }}</td>
                    <td>{{ $row->product->name }}</td>
                    <td>{{ $row->product->unit->symbol }}</td>
                    <td class="text-right font-mono">{{ rtrim(rtrim(number_format((float) $row->system_qty, 3, ',', '.'), '0'), ',') }}</td>
                    <td class="text-right font-mono font-bold">{{ rtrim(rtrim(number_format((float) $row->physical_qty, 3, ',', '.'), '0'), ',') }}</td>
                    <td class="text-right font-mono font-bold {{ (float) $row->difference > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ (float) $row->difference > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $row->difference, 3, ',', '.'), '0'), ',') }}
                    </td>
                    <td>{{ $row->reason_code->label() }}</td>
                    <td style="font-size: 7.5pt;">{{ $row->notes }}</td>
                    <td>{{ $row->creator?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 15px; color: #64748b;">
                        Tidak ada data penyesuaian stok opname yang sesuai dengan filter.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
