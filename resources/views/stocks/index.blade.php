<x-app-layout>
    <x-slot name="header">
        Stok Persediaan Barang
    </x-slot>

    <div class="space-y-6">
        <!-- Toolbar & Filter -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <form method="GET" action="{{ route('stocks.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari kode atau nama barang..."
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Filter Kategori -->
                <div>
                    <select name="category_id" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status Stok (BR-18) -->
                <div>
                    <select name="stock_status" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Status Stok</option>
                        <option value="aman" {{ request('stock_status') === 'aman' ? 'selected' : '' }}>Aman (&gt; Min)</option>
                        <option value="minimum" {{ request('stock_status') === 'minimum' ? 'selected' : '' }}>Minimum (&le; Min)</option>
                        <option value="habis" {{ request('stock_status') === 'habis' ? 'selected' : '' }}>Habis (= 0)</option>
                    </select>
                </div>

                <!-- Filter Status Aktif -->
                <div>
                    <select name="status" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div>
                    <select name="sort_by" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="stock" {{ request('sort_by') === 'stock' || request('sort_by') === 'current_stock' ? 'selected' : '' }}>Urut: Stok</option>
                        <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Urut: Nama Barang</option>
                        <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Urut: Tanggal Dibuat</option>
                    </select>
                </div>

                <!-- Order Direction & Per Page -->
                <div class="sm:col-span-2 md:col-span-3 lg:col-span-6 flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                    <div class="flex items-center space-x-3">
                        <select name="order" class="text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="asc" {{ request('order') === 'asc' ? 'selected' : '' }}>Menaik (Terkecil / A-Z)</option>
                            <option value="desc" {{ request('order') !== 'asc' ? 'selected' : '' }}>Menurun (Terbesar / Z-A)</option>
                        </select>

                        <select name="per_page" class="text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15 baris</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 baris</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 baris</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 baris</option>
                        </select>

                        <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg transition">
                            Terapkan
                        </button>

                        @if(request()->hasAny(['q', 'category_id', 'stock_status', 'status', 'sort_by', 'order', 'per_page']))
                            <a href="{{ route('stocks.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                                Reset Filter
                            </a>
                        @endif
                    </div>

                    <div>
                        <a href="{{ route('stocks.low') }}" class="inline-flex items-center px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-semibold rounded-lg transition">
                            <span class="w-2 h-2 rounded-full bg-amber-500 mr-2"></span>
                            Buka Daftar Stok Minimum
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stock Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                        <tr>
                            <th class="px-6 py-3.5">Kode & Nama Barang</th>
                            <th class="px-6 py-3.5">Kategori</th>
                            <th class="px-6 py-3.5 text-right">Stok Saat Ini</th>
                            <th class="px-6 py-3.5">Satuan</th>
                            <th class="px-6 py-3.5 text-right">Stok Minimum</th>
                            <th class="px-6 py-3.5 text-center">Status Stok</th>
                            <th class="px-6 py-3.5">Lokasi Rak</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($products as $product)
                            @php
                                $stockStatus = $product->stock_status;
                                $statusBadgeClass = match($stockStatus->value) {
                                    'aman' => 'bg-emerald-100 text-emerald-800',
                                    'minimum' => 'bg-amber-100 text-amber-800',
                                    'habis' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-slate-100 text-slate-800',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs font-semibold text-blue-600">{{ $product->code }}</div>
                                    <a href="{{ route('products.show', $product) }}" class="font-medium text-slate-900 hover:text-blue-600 hover:underline">
                                        {{ $product->name }}
                                    </a>
                                </td>

                                <td class="px-6 py-4 text-xs text-slate-600">
                                    {{ $product->category->name }}
                                </td>

                                <td class="px-6 py-4 text-right font-mono font-bold text-slate-900">
                                    {{ rtrim(rtrim(number_format((float) $product->current_stock, 3, ',', '.'), '0'), ',') }}
                                </td>

                                <td class="px-6 py-4 text-xs text-slate-500 font-medium">
                                    {{ $product->unit->symbol }}
                                </td>

                                <td class="px-6 py-4 text-right font-mono text-xs text-slate-500">
                                    {{ rtrim(rtrim(number_format((float) $product->min_stock, 3, ',', '.'), '0'), ',') }}
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
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                    Tidak ada data stok barang yang sesuai dengan filter.
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
