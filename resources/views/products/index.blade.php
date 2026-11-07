<x-app-layout>
    <x-slot name="header">
        Master Barang
    </x-slot>

    <div class="space-y-6" x-data="{ statusModalOpen: false, statusFormAction: '', statusProductName: '', currentStockVal: 0, isCurrentlyActive: false }">
        <!-- Toolbar & Filter -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <form method="GET" action="{{ route('products.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
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
                        <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Urut: Tanggal Dibuat</option>
                        <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Urut: Nama Barang</option>
                        <option value="stock" {{ request('sort_by') === 'stock' ? 'selected' : '' }}>Urut: Stok</option>
                    </select>
                </div>

                <!-- Order Direction & Per Page -->
                <div class="sm:col-span-2 md:col-span-3 lg:col-span-6 flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                    <div class="flex items-center space-x-3">
                        <select name="order" class="text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="desc" {{ request('order') !== 'asc' ? 'selected' : '' }}>Menurun (Z-A / Terbaru)</option>
                            <option value="asc" {{ request('order') === 'asc' ? 'selected' : '' }}>Menaik (A-Z / Terlama)</option>
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
                            <a href="{{ route('products.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                                Reset Filter
                            </a>
                        @endif
                    </div>

                    @can('products.manage')
                        <div>
                            <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Tambah Barang Baru
                            </a>
                        </div>
                    @endcan
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                        <tr>
                            <th class="px-4 py-3.5 text-center">Foto</th>
                            <th class="px-4 py-3.5">Kode & Nama Barang</th>
                            <th class="px-4 py-3.5">Kategori</th>
                            <th class="px-4 py-3.5 text-right">Stok Saat Ini</th>
                            <th class="px-4 py-3.5 text-right">Stok Min</th>
                            <th class="px-4 py-3.5 text-center">Status Stok</th>
                            @if($canViewPrice)
                                <th class="px-4 py-3.5 text-right">Harga Beli</th>
                                <th class="px-4 py-3.5 text-right">Harga Jual</th>
                            @endif
                            <th class="px-4 py-3.5 text-center">Status</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
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
                                <!-- Foto Thumbnail -->
                                <td class="px-4 py-3 text-center">
                                    @if($product->photo_path)
                                        <img
                                            src="{{ asset('storage/'.$product->photo_path) }}"
                                            alt="{{ $product->name }}"
                                            class="w-10 h-10 object-cover rounded-lg border border-slate-200 mx-auto"
                                            loading="lazy"
                                        />
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 mx-auto">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </td>

                                <!-- Kode & Nama -->
                                <td class="px-4 py-3">
                                    <div class="font-mono text-xs font-semibold text-blue-600">{{ $product->code }}</div>
                                    <a href="{{ route('products.show', $product) }}" class="font-medium text-slate-900 hover:text-blue-600 hover:underline">
                                        {{ $product->name }}
                                    </a>
                                    @if($product->storage_location)
                                        <div class="text-[11px] text-slate-400">Lokasi: {{ $product->storage_location }}</div>
                                    @endif
                                </td>

                                <!-- Kategori -->
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ $product->category->name }}
                                    </span>
                                </td>

                                <!-- Stok Saat Ini -->
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                    {{ rtrim(rtrim(number_format((float) $product->current_stock, 3, ',', '.'), '0'), ',') }}
                                    <span class="text-xs text-slate-500 font-normal">{{ $product->unit->symbol }}</span>
                                </td>

                                <!-- Stok Minimum -->
                                <td class="px-4 py-3 text-right text-slate-600 text-xs">
                                    {{ rtrim(rtrim(number_format((float) $product->min_stock, 3, ',', '.'), '0'), ',') }}
                                    <span class="text-slate-400">{{ $product->unit->symbol }}</span>
                                </td>

                                <!-- Status Stok (BR-18) -->
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $statusBadgeClass }}">
                                        {{ $stockStatus->label() }}
                                    </span>
                                </td>

                                <!-- Harga (SA, AG, MG Only) BR-26, AC-PRD-06 -->
                                @if($canViewPrice)
                                    <td class="px-4 py-3 text-right font-mono text-xs text-slate-700">
                                        @currency($product->purchase_price)
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs text-slate-700">
                                        @if($product->selling_price !== null)
                                            @currency($product->selling_price)
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                @endif

                                <!-- Status Aktif -->
                                <td class="px-4 py-3 text-center">
                                    @if($product->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('products.show', $product) }}" class="text-slate-600 hover:text-slate-900 font-medium text-xs">
                                        Detail
                                    </a>

                                    @can('products.manage')
                                        <a href="{{ route('products.edit', $product) }}" class="text-blue-600 hover:text-blue-900 font-medium text-xs">
                                            Ubah
                                        </a>

                                        <button
                                            type="button"
                                            @click="
                                                statusModalOpen = true;
                                                statusFormAction = '{{ route('products.status', $product) }}';
                                                statusProductName = '{{ addslashes($product->name) }} ({{ addslashes($product->code) }})';
                                                currentStockVal = {{ (float) $product->current_stock }};
                                                isCurrentlyActive = {{ $product->is_active ? 'true' : 'false' }};
                                            "
                                            class="text-xs font-medium {{ $product->is_active ? 'text-amber-600 hover:text-amber-900' : 'text-emerald-600 hover:text-emerald-900' }}"
                                        >
                                            {{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canViewPrice ? 10 : 8 }}" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    Tidak ada data barang yang sesuai dengan filter pencarian.
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

        <!-- Status Confirmation Modal (BR-04) -->
        <div
            x-show="statusModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            x-cloak
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="statusModalOpen = false"></div>

                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 relative z-10 border border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-800" x-text="isCurrentlyActive ? 'Konfirmasi Penonaktifan Barang' : 'Konfirmasi Pengaktifan Barang'"></h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Apakah Anda yakin ingin <span class="font-medium" x-text="isCurrentlyActive ? 'menonaktifkan' : 'mengaktifkan'"></span> barang <span class="font-semibold text-slate-800" x-text="statusProductName"></span>?
                    </p>

                    <template x-if="isCurrentlyActive && currentStockVal > 0">
                        <div class="mt-3 p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800">
                            <strong>Peringatan (BR-04):</strong> Barang ini masih memiliki stok (<span x-text="currentStockVal"></span>). Barang hanya dapat dinonaktifkan bila stok saat ini bernilai 0. Penonaktifan akan ditolak oleh sistem.
                        </div>
                    </template>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="statusModalOpen = false"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                        >
                            Batal
                        </button>
                        <form :action="statusFormAction" method="POST">
                            @csrf
                            @method('PATCH')
                            <button
                                type="submit"
                                class="px-4 py-2 text-white text-sm font-medium rounded-lg transition"
                                :class="isCurrentlyActive ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                                x-text="isCurrentlyActive ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan'"
                            >
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
