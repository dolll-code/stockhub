<x-app-layout>
    <x-slot name="header">
        Tambah Master Barang
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6" x-data="{ photoPreview: null }">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-800">Formulir Barang Baru</h2>
                <p class="text-sm text-slate-500 mt-1">Lengkapi data barang persediaan, kategori, satuan, dan saldo awal (bila ada).</p>
            </div>

            <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Kode & Nama Barang -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <label for="code" class="block text-sm font-medium text-slate-700">
                            Kode Barang (Opsional)
                        </label>
                        <input
                            type="text"
                            name="code"
                            id="code"
                            value="{{ old('code') }}"
                            placeholder="Otomatis (BRG-NNNN)"
                            maxlength="30"
                            @input="$el.value = $el.value.toUpperCase()"
                            class="mt-1 block w-full text-sm font-mono uppercase rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('code') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        <p class="mt-1 text-xs text-slate-400">Kosongkan untuk nomor otomatis. Bersifat unik dan immutable.</p>
                        @error('code')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700">
                            Nama Barang <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            required
                            minlength="3"
                            maxlength="150"
                            placeholder="Contoh: Kertas HVS A4 80gr, Mouse Wireless Optik"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('name') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('name')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Kategori & Satuan & Supplier Utama -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <!-- Kategori -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-slate-700">
                            Kategori Barang <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="category_id"
                            id="category_id"
                            required
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('category_id') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >
                            <option value="">Pilih Kategori...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Satuan -->
                    <div>
                        <label for="unit_id" class="block text-sm font-medium text-slate-700">
                            Satuan Barang <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="unit_id"
                            id="unit_id"
                            required
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('unit_id') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >
                            <option value="">Pilih Satuan...</option>
                            @foreach($units as $unit)
                                <option
                                    value="{{ $unit->id }}"
                                    data-allow-decimal="{{ $unit->allow_decimal ? '1' : '0' }}"
                                    {{ old('unit_id') == $unit->id ? 'selected' : '' }}
                                >
                                    {{ $unit->name }} ({{ $unit->symbol }}) - {{ $unit->allow_decimal ? 'Desimal' : 'Bulat' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-400">Satuan terkunci setelah ada mutasi stok.</p>
                        @error('unit_id')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Supplier Utama -->
                    <div>
                        <label for="main_supplier_id" class="block text-sm font-medium text-slate-700">
                            Supplier Utama (Opsional)
                        </label>
                        <select
                            name="main_supplier_id"
                            id="main_supplier_id"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('main_supplier_id') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >
                            <option value="">Tidak ditentukan</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('main_supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('main_supplier_id')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Stok Minimum & Saldo Awal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                    <!-- Stok Minimum -->
                    <div>
                        <label for="min_stock" class="block text-sm font-medium text-slate-700">
                            Stok Minimum <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="number"
                            step="any"
                            min="0"
                            name="min_stock"
                            id="min_stock"
                            value="{{ old('min_stock', 0) }}"
                            required
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('min_stock') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        <p class="mt-1 text-xs text-slate-500">Batas peringatan stok menipis (BR-18). Harus &ge; 0.</p>
                        @error('min_stock')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Saldo Awal (Opening Stock) -->
                    <div>
                        <label for="opening_stock" class="block text-sm font-medium text-slate-700">
                            Stok Awal Registrasi
                        </label>
                        <input
                            type="number"
                            step="any"
                            min="0"
                            name="opening_stock"
                            id="opening_stock"
                            value="{{ old('opening_stock', 0) }}"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('opening_stock') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        <p class="mt-1 text-xs text-slate-500">Bila &gt; 0, sistem membuat mutasi <code class="text-blue-700 font-mono">opening_balance</code> secara aman (BR-17).</p>
                        @error('opening_stock')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Bagian Harga (Khusus SA, AG, MG) BR-26, AC-PRD-06 -->
                @if($canViewPrice)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 p-4 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                        <div>
                            <label for="purchase_price" class="block text-sm font-medium text-slate-700">
                                Estimasi Harga Beli (Rp)
                            </label>
                            <input
                                type="number"
                                step="any"
                                min="0"
                                name="purchase_price"
                                id="purchase_price"
                                value="{{ old('purchase_price', 0) }}"
                                class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('purchase_price') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                            />
                            <p class="mt-1 text-xs text-slate-400">Harga perolehan barang (hanya terlihat peran manajemen).</p>
                            @error('purchase_price')
                                <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="selling_price" class="block text-sm font-medium text-slate-700">
                                Estimasi Harga Jual (Rp) (Opsional)
                            </label>
                            <input
                                type="number"
                                step="any"
                                min="0"
                                name="selling_price"
                                id="selling_price"
                                value="{{ old('selling_price') }}"
                                class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('selling_price') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                            />
                            <p class="mt-1 text-xs text-slate-400">Harga jual acuan bila barang didistribusikan komersial.</p>
                            @error('selling_price')
                                <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                <!-- Lokasi Penyimpanan & Deskripsi -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <label for="storage_location" class="block text-sm font-medium text-slate-700">
                            Lokasi Rak / Gudang (Opsional)
                        </label>
                        <input
                            type="text"
                            name="storage_location"
                            id="storage_location"
                            value="{{ old('storage_location') }}"
                            maxlength="100"
                            placeholder="Contoh: Rak A-02, Gudang 1 Lt 2"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('storage_location') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('storage_location')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-slate-700">
                            Deskripsi Barang (Opsional)
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="2"
                            maxlength="2000"
                            placeholder="Spesifikasi teknis, merek, varian, atau catatan khusus..."
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('description') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Foto Barang (FR-PRD-06, SEC-08) -->
                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Foto Barang (Opsional)
                    </label>
                    <div class="mt-2 flex items-center space-x-6">
                        <!-- Preview Box -->
                        <div class="relative w-24 h-24 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="w-full h-full object-cover" />
                            </template>
                            <template x-if="!photoPreview">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </template>
                        </div>

                        <!-- Upload Input -->
                        <div class="flex-1">
                            <input
                                type="file"
                                name="photo"
                                id="photo"
                                accept="image/jpeg,image/png,image/webp"
                                @change="
                                    const file = $event.target.files[0];
                                    if (file) {
                                        const reader = new FileReader();
                                        reader.onload = (e) => { photoPreview = e.target.result; };
                                        reader.readAsDataURL(file);
                                    } else {
                                        photoPreview = null;
                                    }
                                "
                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                            />
                            <p class="mt-1 text-xs text-slate-400">Format: JPG, JPEG, PNG, WebP (SVG ditolak). Ukuran maksimal 2 MB. Dimensi 100x100 hingga 4000x4000 px.</p>
                            @error('photo')
                                <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Status Aktif -->
                <div class="pt-2">
                    <label class="relative flex items-start cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <div class="ml-3 text-sm">
                            <span class="font-medium text-slate-700">Barang Aktif</span>
                            <p class="text-xs text-slate-500">Barang aktif dapat dipilih dalam transaksi barang masuk dan keluar.</p>
                        </div>
                    </label>
                    @error('is_active')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('products.index') }}"
                        class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                    >
                        Simpan Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
