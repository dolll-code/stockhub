<x-app-layout>
    <x-slot name="header">
        Buat Transaksi Barang Keluar
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6" x-data="stockOutForm()">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Formulir Barang Keluar Baru</h2>
                    <p class="text-sm text-slate-500 mt-1">Dokumen disimpan sebagai draft terlebih dahulu sebelum dikonfirmasi memotong stok (BR-07, BR-10).</p>
                </div>
                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded font-semibold text-xs">Status: Draft</span>
            </div>

            <form method="POST" action="{{ route('stock-outs.store') }}" class="space-y-6">
                @csrf

                <!-- Header Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <!-- Tanggal Transaksi -->
                    <div>
                        <label for="transaction_date" class="block text-sm font-medium text-slate-700">
                            Tanggal Transaksi <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="date"
                            name="transaction_date"
                            id="transaction_date"
                            value="{{ old('transaction_date', now()->toDateString()) }}"
                            required
                            max="{{ now()->toDateString() }}"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('transaction_date') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        <p class="mt-1 text-xs text-slate-400">Maksimal tanggal lampau 7 hari (BR-20).</p>
                        @error('transaction_date')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Peruntukan Pengeluaran -->
                    <div>
                        <label for="purpose_type" class="block text-sm font-medium text-slate-700">
                            Peruntukan Keluar <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="purpose_type"
                            id="purpose_type"
                            required
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('purpose_type') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >
                            @foreach($purposes as $purpose)
                                <option value="{{ $purpose->value }}" {{ old('purpose_type', 'usage') === $purpose->value ? 'selected' : '' }}>
                                    {{ $purpose->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('purpose_type')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tujuan / Penerima -->
                    <div>
                        <label for="destination" class="block text-sm font-medium text-slate-700">
                            Tujuan / Penerima <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="destination"
                            id="destination"
                            value="{{ old('destination') }}"
                            required
                            minlength="3"
                            maxlength="150"
                            placeholder="Contoh: Divisi IT, Proyek A, Pelanggan PT XYZ"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('destination') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('destination')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700">
                        Catatan Tambahan (Opsional)
                    </label>
                    <input
                        type="text"
                        name="notes"
                        id="notes"
                        value="{{ old('notes') }}"
                        maxlength="1000"
                        placeholder="Keterangan pengeluaran..."
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('notes') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    />
                    @error('notes')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Detail Barang (Baris Dinamis Alpine) -->
                <div class="pt-6 border-t border-slate-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-800">Daftar Barang Keluar (1–100 Item)</h3>
                            <p class="text-xs text-slate-500">Pilih barang dan periksa ketersediaan stok sebelum dikonfirmasi.</p>
                        </div>
                        <button
                            type="button"
                            @click="addItem()"
                            :disabled="items.length >= 100"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition disabled:opacity-50"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Tambah Baris Barang
                        </button>
                    </div>

                    @error('items')
                        <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror

                    <!-- Table of Items -->
                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-50 text-slate-600 text-xs font-semibold">
                                <tr>
                                    <th class="px-4 py-3 w-8 text-center">#</th>
                                    <th class="px-4 py-3">Nama Barang</th>
                                    <th class="px-4 py-3 w-44 text-right">Stok Tersedia</th>
                                    <th class="px-4 py-3 w-40 text-right">Kuantitas Keluar</th>
                                    <th class="px-4 py-3 w-28">Satuan</th>
                                    <th class="px-4 py-3 w-16 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <template x-for="(item, index) in items" :key="item.key">
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-4 py-3 text-center text-xs text-slate-400" x-text="index + 1"></td>

                                        <!-- Product Selection -->
                                        <td class="px-4 py-3">
                                            <select
                                                :name="`items[${index}][product_id]`"
                                                x-model="item.product_id"
                                                @change="onProductChange(index)"
                                                required
                                                class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                                            >
                                                <option value="">Pilih Barang...</option>
                                                @foreach($products as $prod)
                                                    <option
                                                        value="{{ $prod->id }}"
                                                        :disabled="isProductSelected({{ $prod->id }}, index)"
                                                    >
                                                        [{{ $prod->code }}] {{ $prod->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <!-- Available Stock Badge (via AJAX) -->
                                        <td class="px-4 py-3 text-right">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-medium"
                                                :class="item.current_stock > 0 ? 'bg-slate-100 text-slate-800' : 'bg-rose-100 text-rose-800'"
                                                x-text="item.current_stock !== null ? formatStock(item.current_stock) : '—'"
                                            ></span>
                                        </td>

                                        <!-- Quantity Input & Warning Banner -->
                                        <td class="px-4 py-3">
                                            <input
                                                type="number"
                                                step="any"
                                                min="0.001"
                                                :name="`items[${index}][quantity]`"
                                                x-model="item.quantity"
                                                required
                                                placeholder="0"
                                                class="w-full text-right text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                                                :class="isInsufficient(item) ? 'border-amber-400 bg-amber-50' : ''"
                                            />
                                            <template x-if="isInsufficient(item)">
                                                <p class="text-[10px] text-amber-700 font-medium mt-0.5 text-right">
                                                    Melebihi stok tersedia (Peringatan Draft, BR-10)
                                                </p>
                                            </template>
                                        </td>

                                        <!-- Unit Symbol -->
                                        <td class="px-4 py-3 text-xs text-slate-500 font-medium" x-text="item.unit_symbol || '—'"></td>

                                        <!-- Delete Line Button -->
                                        <td class="px-4 py-3 text-center">
                                            <button
                                                type="button"
                                                @click="removeItem(index)"
                                                :disabled="items.length <= 1"
                                                class="text-rose-500 hover:text-rose-700 disabled:opacity-30 transition"
                                                title="Hapus Baris"
                                            >
                                                <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('stock-outs.index') }}"
                        class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                    >
                        Simpan Draft Keluar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function stockOutForm() {
            const oldItems = @json(old('items', []));
            let initialItems = [];

            if (Array.isArray(oldItems) && oldItems.length > 0) {
                initialItems = oldItems.map((item, idx) => ({
                    key: Date.now() + idx,
                    product_id: item.product_id || '',
                    quantity: item.quantity || '',
                    current_stock: null,
                    unit_symbol: '',
                }));
            } else {
                initialItems = [{ key: Date.now(), product_id: '', quantity: '', current_stock: null, unit_symbol: '' }];
            }

            return {
                items: initialItems,

                init() {
                    this.items.forEach((item, index) => {
                        if (item.product_id) {
                            this.fetchProductDetails(index, item.product_id);
                        }
                    });
                },

                addItem() {
                    if (this.items.length < 100) {
                        this.items.push({
                            key: Date.now() + Math.random(),
                            product_id: '',
                            quantity: '',
                            current_stock: null,
                            unit_symbol: '',
                        });
                    }
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },

                isProductSelected(productId, currentIndex) {
                    return this.items.some((item, idx) => idx !== currentIndex && item.product_id == productId);
                },

                onProductChange(index) {
                    const productId = this.items[index].product_id;
                    if (!productId) {
                        this.items[index].current_stock = null;
                        this.items[index].unit_symbol = '';
                        return;
                    }
                    this.fetchProductDetails(index, productId);
                },

                fetchProductDetails(index, productId) {
                    fetch(`/ajax/products/${productId}/stock`)
                        .then(res => res.json())
                        .then(json => {
                            if (json.data) {
                                this.items[index].current_stock = parseFloat(json.data.current_stock);
                                this.items[index].unit_symbol = json.data.unit.symbol;
                            }
                        })
                        .catch(() => {});
                },

                isInsufficient(item) {
                    if (item.current_stock === null || item.quantity === '') return false;
                    const q = parseFloat(item.quantity) || 0;
                    return q > item.current_stock;
                },

                formatStock(val) {
                    return (val || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
                }
            };
        }
    </script>
</x-app-layout>
