<x-app-layout>
    <x-slot name="header">
        Detail Barang: {{ $product->name }}
    </x-slot>

    <div class="space-y-6">
        <!-- Product Overview Header -->
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                <div class="flex items-start space-x-4">
                    <!-- Photo or Placeholder -->
                    <div class="w-20 h-20 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                        @if($product->photo_path)
                            <img src="{{ asset('storage/'.$product->photo_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover" />
                        @else
                            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-mono text-xs px-2.5 py-0.5 bg-blue-100 text-blue-800 rounded font-semibold">
                                {{ $product->code }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                {{ $product->category->name }}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 mt-1">{{ $product->name }}</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Dibuat oleh {{ $product->creator?->name ?? 'Sistem' }} pada {{ $product->created_at->isoFormat('D MMMM Y, HH:mm') }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="{{ route('products.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition">
                        Kembali
                    </a>
                    @can('products.manage')
                        <a href="{{ route('products.edit', $product) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            Ubah Barang
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Detail Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 pt-6 text-sm">
                <!-- Stok Saat Ini & Status -->
                <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Stok Saat Ini</span>
                    <div class="mt-1 flex items-baseline space-x-2">
                        <span class="text-2xl font-bold text-slate-900">
                            {{ rtrim(rtrim(number_format((float) $product->current_stock, 3, ',', '.'), '0'), ',') }}
                        </span>
                        <span class="text-sm font-medium text-slate-600">{{ $product->unit->symbol }}</span>
                    </div>
                    <div class="mt-2">
                        @php
                            $stockStatus = $product->stock_status;
                            $badgeClass = match($stockStatus->value) {
                                'aman' => 'bg-emerald-100 text-emerald-800',
                                'minimum' => 'bg-amber-100 text-amber-800',
                                'habis' => 'bg-rose-100 text-rose-800',
                                default => 'bg-slate-100 text-slate-800',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                            Status: {{ $stockStatus->label() }}
                        </span>
                    </div>
                </div>

                <!-- Stok Minimum -->
                <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Stok Minimum</span>
                    <div class="mt-1 flex items-baseline space-x-2">
                        <span class="text-2xl font-bold text-slate-700">
                            {{ rtrim(rtrim(number_format((float) $product->min_stock, 3, ',', '.'), '0'), ',') }}
                        </span>
                        <span class="text-sm font-medium text-slate-500">{{ $product->unit->symbol }}</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">Peringatan saat stok &le; batas minimum.</p>
                </div>

                <!-- Satuan & Tipe -->
                <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Satuan Ukuran</span>
                    <div class="mt-1 font-bold text-slate-800 text-base">
                        {{ $product->unit->name }} ({{ $product->unit->symbol }})
                    </div>
                    <p class="mt-2 text-xs text-slate-500">
                        {{ $product->unit->allow_decimal ? 'Mendukung hingga 3 desimal' : 'Hanya bilangan bulat' }}
                    </p>
                </div>

                <!-- Supplier Utama -->
                <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl">
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Supplier Utama</span>
                    <div class="mt-1 font-bold text-slate-800 text-base truncate">
                        {{ $product->mainSupplier?->name ?? '—' }}
                    </div>
                    @if($product->mainSupplier)
                        <p class="mt-2 text-xs text-blue-600 font-mono">{{ $product->mainSupplier->code }}</p>
                    @else
                        <p class="mt-2 text-xs text-slate-400">Belum ditentukan</p>
                    @endif
                </div>
            </div>

            <!-- Price & Location Details (Conditional Price Visibility BR-26, AC-PRD-06) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-slate-100 mt-6 text-sm">
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block">Lokasi Rak / Gudang</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">{{ $product->storage_location ?? '—' }}</span>
                </div>

                @if($canViewPrice)
                    <div>
                        <span class="text-xs text-slate-400 uppercase tracking-wider block">Estimasi Harga Beli</span>
                        <span class="font-mono font-medium text-slate-900 mt-0.5 block">
                            @currency($product->purchase_price)
                        </span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-400 uppercase tracking-wider block">Estimasi Harga Jual</span>
                        <span class="font-mono font-medium text-slate-900 mt-0.5 block">
                            @if($product->selling_price !== null)
                                @currency($product->selling_price)
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </span>
                    </div>
                @endif
            </div>

            @if($product->description)
                <div class="pt-6 border-t border-slate-100 mt-6">
                    <span class="text-xs text-slate-400 uppercase tracking-wider block">Deskripsi Barang</span>
                    <p class="mt-1 text-sm text-slate-700 leading-relaxed">{{ $product->description }}</p>
                </div>
            @endif
        </div>

        <!-- Kartu Stok Ringkas: 20 Movement Terakhir (FR-PRD-04) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Kartu Stok Ringkas</h3>
                    <p class="text-xs text-slate-500 mt-0.5">20 riwayat mutasi persediaan terakhir untuk barang ini (urut terbaru).</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                        <tr>
                            <th class="px-6 py-3">Waktu & Tanggal</th>
                            <th class="px-6 py-3">Jenis Mutasi</th>
                            <th class="px-6 py-3">No. Referensi</th>
                            <th class="px-6 py-3 text-right">Saldo Awal</th>
                            <th class="px-6 py-3 text-right">Perubahan</th>
                            <th class="px-6 py-3 text-right">Saldo Akhir</th>
                            <th class="px-6 py-3">Petugas</th>
                            <th class="px-6 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($recentMovements as $movement)
                            @php
                                $typeBadgeClass = match($movement->movement_type->value ?? $movement->movement_type) {
                                    'opening_balance' => 'bg-purple-100 text-purple-800',
                                    'stock_in' => 'bg-emerald-100 text-emerald-800',
                                    'stock_out' => 'bg-blue-100 text-blue-800',
                                    'adjustment' => 'bg-amber-100 text-amber-800',
                                    'stock_in_cancel' => 'bg-rose-100 text-rose-800',
                                    'stock_out_cancel' => 'bg-teal-100 text-teal-800',
                                    default => 'bg-slate-100 text-slate-800',
                                };
                                $typeLabel = match($movement->movement_type->value ?? $movement->movement_type) {
                                    'opening_balance' => 'Saldo Awal',
                                    'stock_in' => 'Barang Masuk',
                                    'stock_out' => 'Barang Keluar',
                                    'adjustment' => 'Penyesuaian',
                                    'stock_in_cancel' => 'Batal Masuk',
                                    'stock_out_cancel' => 'Batal Keluar',
                                    default => (string) $movement->movement_type,
                                };

                                $docRoute = null;
                                if ($movement->reference_type && $movement->reference_id) {
                                    if (str_contains($movement->reference_type, 'StockIn')) {
                                        $docRoute = route('stock-ins.show', $movement->reference_id);
                                    } elseif (str_contains($movement->reference_type, 'StockOut')) {
                                        $docRoute = route('stock-outs.show', $movement->reference_id);
                                    } elseif (str_contains($movement->reference_type, 'StockAdjustment')) {
                                        $docRoute = route('adjustments.show', $movement->reference_id);
                                    }
                                }
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-3.5 text-xs text-slate-600 whitespace-nowrap">
                                    {{ $movement->created_at->isoFormat('D MMM Y, HH:mm') }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $typeBadgeClass }}">
                                        {{ $typeLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 font-mono text-xs">
                                    @if($docRoute)
                                        <a href="{{ $docRoute }}" class="text-blue-600 hover:underline font-semibold">
                                            {{ $movement->reference_number ?? 'Dokumen' }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">{{ $movement->reference_number ?? '—' }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3.5 text-right font-mono text-xs text-slate-600">
                                    {{ rtrim(rtrim(number_format((float) $movement->qty_before, 3, ',', '.'), '0'), ',') }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-mono text-xs font-bold {{ (float) $movement->qty_change > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ (float) $movement->qty_change > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $movement->qty_change, 3, ',', '.'), '0'), ',') }}
                                </td>
                                <td class="px-6 py-3.5 text-right font-mono text-xs font-bold text-slate-900">
                                    {{ rtrim(rtrim(number_format((float) $movement->qty_after, 3, ',', '.'), '0'), ',') }}
                                </td>
                                <td class="px-6 py-3.5 text-xs text-slate-600">
                                    {{ $movement->user?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-3.5 text-xs text-slate-500 max-w-xs truncate">
                                    {{ $movement->notes ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Belum ada riwayat mutasi stok untuk barang ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
