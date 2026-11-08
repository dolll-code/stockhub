<x-app-layout>
    <x-slot name="header">
        Buat Transaksi Barang Masuk
    </x-slot>

    <div class="max-w-5xl mx-auto space-y-6" x-data="stockInForm()">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Formulir Barang Masuk Baru</h2>
                    <p class="text-sm text-slate-500 mt-1">Dokumen disimpan sebagai draft terlebih dahulu sebelum dikonfirmasi menambah stok (BR-07).</p>
                </div>
                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded font-semibold text-xs">Status: Draft</span>
            </div>

            <form method="POST" action="{{ route('stock-ins.store') }}" class="space-y-6">
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

                    <!-- Sumber Barang Masuk -->
                    <div>
                        <label for="source_type" class="block text-sm font-medium text-slate-700">
                            Sumber Masuk <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="source_type"
                            id="source_type"
                            x-model="sourceType"
                            required
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('source_type') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >
                            @foreach($sources as $source)
                                <option value="{{ $source->value }}" {{ old('source_type', 'purchase') === $source->value ? 'selected' : '' }}>
                                    {{ $source->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('source_type')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Supplier -->
                    <div>
                        <label for="supplier_id" class="block text-sm font-medium text-slate-700">
                            Supplier <span x-show="sourceType === 'purchase'" class="text-rose-500">*</span>
                        </label>
                        <select
                            name="supplier_id"
                            id="supplier_id"
                            :required="sourceType === 'purchase'"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('supplier_id') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >
                            <option value="">Pilih Supplier...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->code }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-400">Wajib jika sumber adalah pembelian.</p>
                        @error('supplier_id')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Dokumen Referensi & Catatan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="reference_document" class="block text-sm font-medium text-slate-700">
                            Nomor Dokumen Referensi (Opsional)
                        </label>
                        <input
                            type="text"
                            name="reference_document"
                            id="reference_document"
                            value="{{ old('reference_document') }}"
                            maxlength="100"
                            placeholder="Contoh: No. Surat Jalan / No. PO / Faktur"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('reference_document') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('reference_document')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

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
                            placeholder="Keterangan singkat..."
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('notes') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('notes')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Detail Barang (Baris Dinamis Alpine) -->
                <div class="pt-6 border-t border-slate-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-800">Daftar Barang Masuk (1–100 Item)</h3>
                            <p class="text-xs text-slate-500">Pilih barang dan tentukan kuantitas yang diterima.</p>
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
                                    <th class="px-4 py-3 w-40 text-right">Kuantitas</th>
                                    <th class="px-4 py-3 w-28">Satuan</th>
                                    @if($canViewPrice)
                                        <th class="px-4 py-3 w-36 text-right">Harga Satuan (Rp)</th>
                                        <th class="px-4 py-3 w-40 text-right">Subtotal</th>
                                    @endif
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
                                                        data-unit="{{ $prod->unit->symbol }}"
                                                        data-allow-decimal="{{ $prod->unit->allow_decimal ? '1' : '0' }}"
                                                        data-price="{{ $prod->purchase_price }}"
                                                    >
                                                        [{{ $prod->code }}] {{ $prod->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <!-- Quantity -->
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
                                            />
                                        </td>

                                        <!-- Unit Symbol -->
                                        <td class="px-4 py-3 text-xs text-slate-500 font-medium" x-text="item.unit_symbol || '—'"></td>

                                        <!-- Unit Price & Subtotal (Visible only for SA/AG/MG) -->
                                        @if($canViewPrice)
                                            <td class="px-4 py-3">
                                                <input
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    :name="`items[${index}][unit_price]`"
                                                    x-model="item.unit_price"
                                                    placeholder="0"
                                                    class="w-full text-right text-xs font-mono rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                                                />
                                            </td>

                                            <td class="px-4 py-3 text-right text-xs font-mono font-medium text-slate-900" x-text="formatCurrency(calculateSubtotal(item))"></td>
                                        @endif

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
                            @if($canViewPrice)
                                <tfoot class="bg-slate-50 border-t border-slate-200">
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-right font-semibold text-slate-700 text-xs uppercase tracking-wider">
                                            Total Estimasi Nilai Masuk
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 text-sm" x-text="formatCurrency(calculateTotal())"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('stock-ins.index') }}"
                        class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                    >
                        Simpan Draft Masuk
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function stockInForm() {
            const oldItems = @json(old('items', []));
            let initialItems = [];

            if (Array.isArray(oldItems) && oldItems.length > 0) {
                initialItems = oldItems.map((item, idx) => ({
                    key: Date.now() + idx,
                    product_id: item.product_id || '',
                    quantity: item.quantity || '',
                    unit_price: item.unit_price || '',
                    unit_symbol: '',
                }));
            } else {
                initialItems = [{ key: Date.now(), product_id: '', quantity: '', unit_price: '', unit_symbol: '' }];
            }

            return {
                sourceType: '{{ old('source_type', 'purchase') }}',
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
                            unit_price: '',
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
                        this.items[index].unit_symbol = '';
                        this.items[index].unit_price = '';
                        return;
                    }
                    this.fetchProductDetails(index, productId);
                },

                fetchProductDetails(index, productId) {
                    fetch(`/ajax/products/${productId}/stock`)
                        .then(res => res.json())
                        .then(json => {
                            if (json.data) {
                                this.items[index].unit_symbol = json.data.unit.symbol;
                                if (json.data.purchase_price && !this.items[index].unit_price) {
                                    this.items[index].unit_price = json.data.purchase_price;
                                }
                            }
                        })
                        .catch(() => {});
                },

                calculateSubtotal(item) {
                    const q = parseFloat(item.quantity) || 0;
                    const p = parseFloat(item.unit_price) || 0;
                    return q * p;
                },

                calculateTotal() {
                    return this.items.reduce((total, item) => total + this.calculateSubtotal(item), 0);
                },

                formatCurrency(amount) {
                    return 'Rp ' + (amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                }
            };
        }
    </script>
</x-app-layout>
