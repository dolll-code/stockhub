<x-app-layout>
    <x-slot name="header">
        Stok Minimum (Barang Kritis)
    </x-slot>

    <div class="space-y-6">
        <!-- Info Banner (FR-STK-02, BR-18) -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 sm:p-5 flex items-start space-x-3 text-amber-900 text-sm">
            <svg class="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <span class="font-bold">Daftar Barang Kritis Persediaan Gudang</span>
                <p class="text-xs text-amber-800 mt-1">
                    Halaman ini hanya menampilkan <strong>barang aktif</strong> yang berada pada status <span class="font-semibold text-rose-700">Habis</span> (stok = 0) atau <span class="font-semibold text-amber-700">Minimum</span> (0 &lt; stok &le; batas minimum). Barang habis ditampilkan di posisi teratas dan diurutkan berdasarkan rasio ketersediaan paling kritis (FR-STK-02).
                </p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <form method="GET" action="{{ route('stocks.low') }}" class="flex-1 flex flex-wrap items-center gap-3">
                <div class="w-full sm:w-64">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari kode atau nama barang..."
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <select name="category_id" class="text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg transition">
                    Filter
                </button>

                @if(request()->hasAny(['q', 'category_id']))
                    <a href="{{ route('stocks.low') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                        Reset Filter
                    </a>
                @endif
            </form>

            <div class="flex items-center space-x-2">
                @can('stock-ins.create')
                    <a href="{{ route('stock-ins.create') }}" class="inline-flex items-center px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Buat Pengadaan Masuk
                    </a>
                @endcan
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                        <tr>
                            <th class="px-6 py-3.5">Nama & Kode Barang</th>
                            <th class="px-6 py-3.5">Kategori</th>
                            <th class="px-6 py-3.5 text-right">Stok Tersedia</th>
                            <th class="px-6 py-3.5 text-right">Stok Minimum</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5">Lokasi Rak</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($products as $product)
                            @php
                                $stockStatus = $product->stock_status;
                                $statusBadgeClass = match($stockStatus->value) {
                                    'habis' => 'bg-rose-100 text-rose-800',
                                    'minimum' => 'bg-amber-100 text-amber-800',
                                    default => 'bg-slate-100 text-slate-800',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $product->name }}</div>
                                    <div class="font-mono text-xs text-blue-600 font-semibold">{{ $product->code }}</div>
                                </td>

                                <td class="px-6 py-4 text-xs text-slate-600">
                                    {{ $product->category->name }}
                                </td>

                                <td class="px-6 py-4 text-right font-mono font-bold text-base {{ (float) $product->current_stock <= 0 ? 'text-rose-600' : 'text-amber-700' }}">
                                    {{ rtrim(rtrim(number_format((float) $product->current_stock, 3, ',', '.'), '0'), ',') }}
                                    <span class="text-xs text-slate-400 font-normal">{{ $product->unit->symbol }}</span>
                                </td>

                                <td class="px-6 py-4 text-right font-mono text-xs text-slate-600">
                                    {{ rtrim(rtrim(number_format((float) $product->min_stock, 3, ',', '.'), '0'), ',') }}
                                    <span class="text-xs text-slate-400 font-normal">{{ $product->unit->symbol }}</span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusBadgeClass }}">
                                        {{ $stockStatus->label() }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $product->storage_location ?? '—' }}
                                </td>

                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('products.show', $product) }}" class="text-blue-600 hover:text-blue-900 font-medium text-xs">
                                        Kartu Stok
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Luar biasa! Tidak ada barang aktif yang kehabisan stok atau di bawah batas minimum.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
