<x-app-layout>
    <x-slot name="header">
        Ubah Barang: {{ $product->name }}
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6" x-data="{ photoPreview: null, removeExistingPhoto: false }">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Ubah Data Barang</h2>
                    <p class="text-sm text-slate-500 mt-1">Perbarui informasi barang, kategori, stok minimum, atau foto.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 font-mono">
                        Stok: {{ rtrim(rtrim(number_format((float) $product->current_stock, 3, ',', '.'), '0'), ',') }} {{ $product->unit->symbol }}
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Kode (Immutable BR-05) & Nama Barang -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Kode Barang
                        </label>
                        <input
                            type="text"
                            value="{{ $product->code }}"
                            disabled
                            class="mt-1 block w-full text-sm font-mono bg-slate-100 text-slate-500 cursor-not-allowed rounded-lg border-slate-300"
                        />
                        <p class="mt-1 text-xs text-slate-400">Kode barang bersifat tetap (immutable, BR-05).</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700">
                            Nama Barang <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $product->name) }}"
                            required
                            minlength="3"
                            maxlength="150"
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
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} {{ ! $category->is_active ? '(Nonaktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Satuan (Locked if has movements BR-25) -->
                    <div>
                        <div class="flex items-center justify-between">
                            <label for="unit_id" class="block text-sm font-medium text-slate-700">
                                Satuan Barang <span class="text-rose-500">*</span>
                            </label>
                            @if($isUnitLocked)
                                <span class="text-[10px] font-semibold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded">
                                    Terkunci (BR-25)
                                </span>
                            @endif
                        </div>

                        @if($isUnitLocked)
                            <input type="hidden" name="unit_id" value="{{ $product->unit_id }}">
                            <input
                                type="text"
                                value="{{ $product->unit->name }} ({{ $product->unit->symbol }})"
                                disabled
                                class="mt-1 block w-full text-sm bg-slate-100 text-slate-500 cursor-not-allowed rounded-lg border-slate-300"
                            />
                            <p class="mt-1 text-xs text-slate-400">Satuan terkunci karena sudah memiliki riwayat mutasi stok.</p>
                        @else
                            <select
                                name="unit_id"
                                id="unit_id"
                                required
                                class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('unit_id') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                            >
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }} ({{ $unit->symbol }}) {{ ! $unit->is_active ? '(Nonaktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
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
                                <option value="{{ $supplier->id }}" {{ old('main_supplier_id', $product->main_supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->code }}) {{ ! $supplier->is_active ? '(Nonaktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('main_supplier_id')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Stok Minimum & Stok Saat Ini Info -->
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
                            value="{{ old('min_stock', (float) $product->min_stock) }}"
                            required
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('min_stock') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        <p class="mt-1 text-xs text-slate-500">Perubahan stok minimum langsung mempengaruhi penentuan status stok (BR-29).</p>
                        @error('min_stock')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Stok Saat Ini (Readonly - BR-06) -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Stok Saat Ini (Sistem)
                        </label>
                        <div class="mt-1 flex items-center justify-between p-2.5 bg-white border border-slate-300 rounded-lg">
                            <span class="font-bold text-slate-900">
                                {{ rtrim(rtrim(number_format((float) $product->current_stock, 3, ',', '.'), '0'), ',') }} {{ $product->unit->symbol }}
                            </span>
                            <span class="text-xs text-slate-400">Diubah hanya melalui mutasi transaksi (BR-06)</span>
                        </div>
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
                                value="{{ old('purchase_price', (float) $product->purchase_price) }}"
                                class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('purchase_price') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                            />
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
                                value="{{ old('selling_price', $product->selling_price !== null ? (float) $product->selling_price : '') }}"
                                class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('selling_price') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                            />
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
                            Lokasi Rak / Gudang
                        </label>
                        <input
                            type="text"
                            name="storage_location"
                            id="storage_location"
                            value="{{ old('storage_location', $product->storage_location) }}"
                            maxlength="100"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('storage_location') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('storage_location')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-slate-700">
                            Deskripsi Barang
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="2"
                            maxlength="2000"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('description') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Foto Barang (FR-PRD-06, SEC-08) -->
                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Foto Barang
                    </label>

                    <div class="mt-2 flex items-start space-x-6">
                        <!-- Preview Box -->
                        <div class="relative w-28 h-28 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="w-full h-full object-cover" />
                            </template>
                            <template x-if="!photoPreview && !removeExistingPhoto && '{{ $product->photo_path }}'">
                                <img src="{{ asset('storage/'.$product->photo_path) }}" class="w-full h-full object-cover" />
                            </template>
                            <template x-if="!photoPreview && (removeExistingPhoto || !'{{ $product->photo_path }}')">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </template>
                        </div>

                        <!-- Upload & Removal Controls -->
                        <div class="flex-1 space-y-3">
                            <input
                                type="file"
                                name="photo"
                                id="photo"
                                accept="image/jpeg,image/png,image/webp"
                                @change="
                                    const file = $event.target.files[0];
                                    if (file) {
                                        removeExistingPhoto = false;
                                        const reader = new FileReader();
                                        reader.onload = (e) => { photoPreview = e.target.result; };
                                        reader.readAsDataURL(file);
                                    } else {
                                        photoPreview = null;
                                    }
                                "
                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                            />
                            <p class="text-xs text-slate-400">Pilih file baru untuk mengganti foto. Format JPG, JPEG, PNG, WebP (maks 2 MB, 100x100 hingga 4000x4000 px).</p>

                            @if($product->photo_path)
                                <div class="pt-1">
                                    <label class="inline-flex items-center text-xs text-rose-600 cursor-pointer font-medium">
                                        <input
                                            type="checkbox"
                                            name="remove_photo"
                                            value="1"
                                            @change="removeExistingPhoto = $event.target.checked; if ($event.target.checked) photoPreview = null;"
                                            class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 h-3.5 w-3.5 mr-1.5"
                                        />
                                        Hapus foto yang saat ini terpasang
                                    </label>
                                </div>
                            @endif

                            @error('photo')
                                <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
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
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
