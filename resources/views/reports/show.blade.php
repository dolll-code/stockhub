<x-app-layout>
    <x-slot name="header">
        Laporan Persediaan & Mutasi
    </x-slot>

    <div class="space-y-6">
        <!-- Tab Navigation (5 Laporan Resmi PRD §5.12) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-1.5 flex flex-wrap gap-1 text-sm font-medium">
            <a
                href="{{ route('reports.show', 'stock') }}"
                class="px-4 py-2 rounded-lg transition {{ $type === 'stock' ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
            >
                1. Laporan Persediaan
            </a>
            <a
                href="{{ route('reports.show', 'stock-ins') }}"
                class="px-4 py-2 rounded-lg transition {{ $type === 'stock-ins' ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
            >
                2. Laporan Barang Masuk
            </a>
            <a
                href="{{ route('reports.show', 'stock-outs') }}"
                class="px-4 py-2 rounded-lg transition {{ $type === 'stock-outs' ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
            >
                3. Laporan Barang Keluar
            </a>
            <a
                href="{{ route('reports.show', 'adjustments') }}"
                class="px-4 py-2 rounded-lg transition {{ $type === 'adjustments' ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
            >
                4. Laporan Penyesuaian
            </a>
            <a
                href="{{ route('reports.show', 'movements') }}"
                class="px-4 py-2 rounded-lg transition {{ $type === 'movements' ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
            >
                5. Laporan Pergerakan Stok
            </a>
        </div>

        <!-- Filter & Header Card -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-800">
                        @if($type === 'stock') Laporan Persediaan (Kondisi Stok)
                        @elseif($type === 'stock-ins') Laporan Transaksi Barang Masuk (Confirmed)
                        @elseif($type === 'stock-outs') Laporan Transaksi Barang Keluar (Confirmed)
                        @elseif($type === 'adjustments') Laporan Penyesuaian Stok Opname
                        @elseif($type === 'movements') Laporan Riwayat Pergerakan Stok (Buku Besar)
                        @endif
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menampilkan data persediaan berdasarkan kriteria filter aktif (Query Object terpusat, BR-31).
                    </p>
                </div>

                <!-- Export Buttons (FR-EXP-01..04) -->
                @can('reports.export')
                    <div class="flex items-center space-x-2">
                        <a
                            href="{{ route('reports.export', array_merge(['type' => $type, 'format' => 'pdf'], request()->query())) }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 shadow-sm transition"
                            title="Unduh laporan dalam format dokumen PDF"
                        >
                            <svg class="w-4 h-4 mr-1 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            Ekspor PDF
                        </a>

                        <a
                            href="{{ route('reports.export', array_merge(['type' => $type, 'format' => 'xlsx'], request()->query())) }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 shadow-sm transition"
                            title="Unduh laporan dalam format berkas Excel (.xlsx)"
                        >
                            <svg class="w-4 h-4 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Ekspor Excel (.xlsx)
                        </a>
                    </div>
                @endcan
            </div>

            <!-- Specific Filter Forms per Report Type -->
            <form method="GET" action="{{ route('reports.show', $type) }}" class="space-y-3">
                @if($type === 'stock')
                    <!-- Filter Laporan Persediaan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 text-xs">
                        <div>
                            <label class="block text-slate-500 font-medium mb-1">Kategori</label>
                            <select name="category_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-500 font-medium mb-1">Status Keaktifan</label>
                            <select name="status" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif Saja</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif Saja</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-500 font-medium mb-1">Status Stok</label>
                            <select name="stock_status" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Status Stok</option>
                                <option value="aman" {{ request('stock_status') === 'aman' ? 'selected' : '' }}>Aman (&gt; Min)</option>
                                <option value="minimum" {{ request('stock_status') === 'minimum' ? 'selected' : '' }}>Minimum (&le; Min)</option>
                                <option value="habis" {{ request('stock_status') === 'habis' ? 'selected' : '' }}>Habis (= 0)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-500 font-medium mb-1">Per Tanggal (As-of Date)</label>
                            <input
                                type="date"
                                name="as_of_date"
                                value="{{ request('as_of_date') }}"
                                max="{{ now()->toDateString() }}"
                                class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-slate-500 font-medium mb-1">Baris per Halaman</label>
                            <select name="per_page" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15 baris</option>
                                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 baris</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 baris</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 baris</option>
                            </select>
                        </div>
                    </div>
                @else
                    <!-- Filter Laporan Transaksi / Mutasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 text-xs">
                        <div>
                            <label class="block text-slate-500 font-medium mb-1">Dari Tanggal</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" />
                        </div>

                        <div>
                            <label class="block text-slate-500 font-medium mb-1">Sampai Tanggal</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" />
                        </div>

                        <div>
                            <label class="block text-slate-500 font-medium mb-1">Barang</label>
                            <select name="product_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Barang</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>[{{ $p->code }}] {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-500 font-medium mb-1">Kategori</label>
                            <select name="category_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if($type === 'stock-ins')
                            <div>
                                <label class="block text-slate-500 font-medium mb-1">Supplier</label>
                                <select name="supplier_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Supplier</option>
                                    @foreach($suppliers as $s)
                                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-500 font-medium mb-1">Sumber</label>
                                <select name="source_type" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Sumber</option>
                                    @foreach($sources as $src)
                                        <option value="{{ $src->value }}" {{ request('source_type') === $src->value ? 'selected' : '' }}>{{ $src->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($type === 'stock-outs')
                            <div>
                                <label class="block text-slate-500 font-medium mb-1">Peruntukan</label>
                                <select name="purpose_type" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Peruntukan</option>
                                    @foreach($purposes as $prp)
                                        <option value="{{ $prp->value }}" {{ request('purpose_type') === $prp->value ? 'selected' : '' }}>{{ $prp->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($type === 'adjustments')
                            <div>
                                <label class="block text-slate-500 font-medium mb-1">Alasan</label>
                                <select name="reason_code" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Alasan</option>
                                    @foreach($reasons as $rsn)
                                        <option value="{{ $rsn->value }}" {{ request('reason_code') === $rsn->value ? 'selected' : '' }}>{{ $rsn->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($type === 'movements')
                            <div>
                                <label class="block text-slate-500 font-medium mb-1">Jenis Mutasi</label>
                                <select name="movement_type" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Semua Jenis</option>
                                    @foreach($movementTypes as $mt)
                                        <option value="{{ $mt->value }}" {{ request('movement_type') === $mt->value ? 'selected' : '' }}>{{ $mt->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <div class="flex items-center space-x-2">
                        <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg transition">
                            Terapkan Filter
                        </button>
                        @if(request()->hasAny(['category_id', 'product_id', 'status', 'stock_status', 'as_of_date', 'date_from', 'date_to', 'supplier_id', 'source_type', 'purpose_type', 'reason_code', 'movement_type', 'per_page']))
                            <a href="{{ route('reports.show', $type) }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                                Reset
                            </a>
                        @endif
                    </div>

                    <p class="text-[11px] text-slate-400">
                        Aturan BR-27: Tidak ada penjumlahan total kuantitas lintas satuan yang berbeda.
                    </p>
                </div>
            </form>
        </div>

        <!-- Report Table Area -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                @if($type === 'stock')
                    <!-- TABEL PERSYARATAN 1: PERSEDIAAN -->
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                            <tr>
                                <th class="px-6 py-3.5">Kode Barang</th>
                                <th class="px-6 py-3.5">Nama Barang</th>
                                <th class="px-6 py-3.5">Kategori</th>
                                <th class="px-6 py-3.5 text-right">
                                    {{ request('as_of_date') ? 'Stok per '.Carbon\Carbon::parse(request('as_of_date'))->format('d/m/Y') : 'Stok Saat Ini' }}
                                </th>
                                <th class="px-6 py-3.5">Satuan</th>
                                <th class="px-6 py-3.5 text-right">Stok Min</th>
                                <th class="px-6 py-3.5 text-center">Status Stok</th>
                                @if($canViewPrice)
                                    <th class="px-6 py-3.5 text-right">Harga Beli</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($reportData as $row)
                                @php
                                    $stokVal = isset($row->as_of_stock) ? (float) $row->as_of_stock : (float) $row->current_stock;
                                    $status = \App\Enums\StockStatus::fromStock($stokVal, $row->min_stock);
                                    $badge = match($status->value) {
                                        'aman' => 'bg-emerald-100 text-emerald-800',
                                        'minimum' => 'bg-amber-100 text-amber-800',
                                        'habis' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-slate-100 text-slate-800',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 font-mono font-medium text-xs text-blue-600">{{ $row->code }}</td>
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $row->name }}</td>
                                    <td class="px-6 py-4 text-xs text-slate-600">{{ $row->category->name }}</td>
                                    <td class="px-6 py-4 text-right font-mono font-bold text-slate-900">
                                        {{ rtrim(rtrim(number_format($stokVal, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500">{{ $row->unit->symbol }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-xs text-slate-500">
                                        {{ rtrim(rtrim(number_format((float) $row->min_stock, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badge }}">
                                            {{ $status->label() }}
                                        </span>
                                    </td>
                                    @if($canViewPrice)
                                        <td class="px-6 py-4 text-right font-mono text-xs text-slate-700">
                                            @currency($row->purchase_price)
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $canViewPrice ? 8 : 7 }}" class="px-6 py-12 text-center text-slate-400">Tidak ada data persediaan barang yang sesuai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                @elseif($type === 'stock-ins')
                    <!-- TABEL PERSYARATAN 2: BARANG MASUK -->
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                            <tr>
                                <th class="px-5 py-3.5">No. Dokumen</th>
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5">Sumber</th>
                                <th class="px-5 py-3.5">Supplier</th>
                                <th class="px-5 py-3.5">Barang</th>
                                <th class="px-5 py-3.5 text-right">Jumlah</th>
                                <th class="px-5 py-3.5">Satuan</th>
                                @if($canViewPrice)
                                    <th class="px-5 py-3.5 text-right">Harga Satuan</th>
                                    <th class="px-5 py-3.5 text-right">Subtotal</th>
                                @endif
                                <th class="px-5 py-3.5">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($reportData as $row)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-4 font-mono font-medium text-xs text-blue-600">
                                        <a href="{{ route('stock-ins.show', $row->stock_in_id) }}" class="hover:underline">
                                            {{ $row->stockIn->number }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600 whitespace-nowrap">{{ $row->stockIn->transaction_date->isoFormat('D/MM/Y') }}</td>
                                    <td class="px-5 py-4 text-xs font-medium">{{ $row->stockIn->source_type->label() }}</td>
                                    <td class="px-5 py-4 text-xs text-slate-600">{{ $row->stockIn->supplier?->name ?? '—' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="font-mono text-[11px] text-slate-500">{{ $row->product->code }}</div>
                                        <div class="font-medium text-slate-900 text-xs">{{ $row->product->name }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono font-semibold text-slate-900">
                                        {{ rtrim(rtrim(number_format((float) $row->quantity, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-500">{{ $row->product->unit->symbol }}</td>
                                    @if($canViewPrice)
                                        <td class="px-5 py-4 text-right font-mono text-xs text-slate-700">@currency($row->unit_price)</td>
                                        <td class="px-5 py-4 text-right font-mono text-xs font-semibold text-slate-900">@currency($row->subtotal)</td>
                                    @endif
                                    <td class="px-5 py-4 text-xs text-slate-600">{{ $row->stockIn->creator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $canViewPrice ? 10 : 8 }}" class="px-6 py-12 text-center text-slate-400">Tidak ada data transaksi barang masuk confirmed yang sesuai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                @elseif($type === 'stock-outs')
                    <!-- TABEL PERSYARATAN 3: BARANG KELUAR -->
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                            <tr>
                                <th class="px-6 py-3.5">No. Dokumen</th>
                                <th class="px-6 py-3.5">Tanggal</th>
                                <th class="px-6 py-3.5">Peruntukan</th>
                                <th class="px-6 py-3.5">Tujuan / Penerima</th>
                                <th class="px-6 py-3.5">Barang</th>
                                <th class="px-6 py-3.5 text-right">Jumlah</th>
                                <th class="px-6 py-3.5">Satuan</th>
                                <th class="px-6 py-3.5">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($reportData as $row)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 font-mono font-medium text-xs text-blue-600">
                                        <a href="{{ route('stock-outs.show', $row->stock_out_id) }}" class="hover:underline">
                                            {{ $row->stockOut->number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-600 whitespace-nowrap">{{ $row->stockOut->transaction_date->isoFormat('D/MM/Y') }}</td>
                                    <td class="px-6 py-4 text-xs font-medium">{{ $row->stockOut->purpose_type->label() }}</td>
                                    <td class="px-6 py-4 text-xs text-slate-800">{{ $row->stockOut->destination }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-mono text-[11px] text-slate-500">{{ $row->product->code }}</div>
                                        <div class="font-medium text-slate-900 text-xs">{{ $row->product->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono font-semibold text-slate-900">
                                        {{ rtrim(rtrim(number_format((float) $row->quantity, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-slate-500">{{ $row->product->unit->symbol }}</td>
                                    <td class="px-6 py-4 text-xs text-slate-600">{{ $row->stockOut->creator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400">Tidak ada data transaksi barang keluar confirmed yang sesuai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                @elseif($type === 'adjustments')
                    <!-- TABEL PERSYARATAN 4: PENYESUAIAN STOK -->
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                            <tr>
                                <th class="px-5 py-3.5">No. Dokumen</th>
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5">Barang</th>
                                <th class="px-5 py-3.5">Satuan</th>
                                <th class="px-5 py-3.5 text-right">Stok Sistem</th>
                                <th class="px-5 py-3.5 text-right">Stok Fisik</th>
                                <th class="px-5 py-3.5 text-right">Selisih</th>
                                <th class="px-5 py-3.5">Alasan</th>
                                <th class="px-5 py-3.5">Keterangan</th>
                                <th class="px-5 py-3.5">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($reportData as $row)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-4 font-mono font-medium text-xs text-blue-600">
                                        <a href="{{ route('adjustments.show', $row) }}" class="hover:underline">
                                            {{ $row->number }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600 whitespace-nowrap">{{ $row->adjustment_date->isoFormat('D/MM/Y') }}</td>
                                    <td class="px-5 py-4">
                                        <div class="font-mono text-[11px] text-slate-500">{{ $row->product->code }}</div>
                                        <div class="font-medium text-slate-900 text-xs">{{ $row->product->name }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-500">{{ $row->product->unit->symbol }}</td>
                                    <td class="px-5 py-4 text-right font-mono text-xs text-slate-500">
                                        {{ rtrim(rtrim(number_format((float) $row->system_qty, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono text-xs font-semibold text-slate-800">
                                        {{ rtrim(rtrim(number_format((float) $row->physical_qty, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono text-xs font-bold {{ (float) $row->difference > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ (float) $row->difference > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $row->difference, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-5 py-4 text-xs">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-800">
                                            {{ $row->reason_code->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-500 max-w-xs truncate" title="{{ $row->notes }}">{{ $row->notes }}</td>
                                    <td class="px-5 py-4 text-xs text-slate-600">{{ $row->creator?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-6 py-12 text-center text-slate-400">Tidak ada data penyesuaian stok opname yang sesuai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                @elseif($type === 'movements')
                    <!-- TABEL PERSYARATAN 5: PERGERAKAN STOK -->
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                            <tr>
                                <th class="px-5 py-3.5">Tanggal & Waktu</th>
                                <th class="px-5 py-3.5">Barang</th>
                                <th class="px-5 py-3.5">Jenis Mutasi</th>
                                <th class="px-5 py-3.5">No. Referensi</th>
                                <th class="px-5 py-3.5 text-right">Saldo Sebelum</th>
                                <th class="px-5 py-3.5 text-right">Perubahan</th>
                                <th class="px-5 py-3.5 text-right">Saldo Sesudah</th>
                                <th class="px-5 py-3.5">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($reportData as $row)
                                @php
                                    $typeVal = $row->movement_type->value ?? $row->movement_type;
                                    $badgeClass = match($typeVal) {
                                        'opening_balance' => 'bg-purple-100 text-purple-800',
                                        'stock_in' => 'bg-emerald-100 text-emerald-800',
                                        'stock_out' => 'bg-blue-100 text-blue-800',
                                        'adjustment' => 'bg-amber-100 text-amber-800',
                                        'stock_in_cancel' => 'bg-rose-100 text-rose-800',
                                        'stock_out_cancel' => 'bg-teal-100 text-teal-800',
                                        default => 'bg-slate-100 text-slate-800',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-5 py-4 text-xs text-slate-600 whitespace-nowrap">
                                        <div>{{ $row->created_at->isoFormat('D/MM/Y HH:mm') }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-mono text-[11px] text-slate-500">{{ $row->product->code }}</div>
                                        <div class="font-medium text-slate-900 text-xs">{{ $row->product->name }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $badgeClass }}">
                                            {{ $row->movement_type->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs text-slate-600">
                                        {{ $row->reference_number ?? '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono text-xs text-slate-500">
                                        {{ rtrim(rtrim(number_format((float) $row->qty_before, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono text-xs font-bold {{ (float) $row->qty_change > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ (float) $row->qty_change > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $row->qty_change, 3, ',', '.'), '0'), ',') }}
                                        <span class="text-[10px] text-slate-400 font-normal">{{ $row->product->unit->symbol }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right font-mono text-xs font-bold text-slate-900">
                                        {{ rtrim(rtrim(number_format((float) $row->qty_after, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600">{{ $row->user?->name ?? 'Sistem' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400">Tidak ada catatan pergerakan stok yang sesuai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>

            @if($reportData->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $reportData->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
