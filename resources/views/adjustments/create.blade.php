<x-app-layout>
    <x-slot name="header">
        Buat Penyesuaian Stok Opname
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6" x-data="adjustmentForm()">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Formulir Penyesuaian Stok Fisik</h2>
                    <p class="text-sm text-slate-500 mt-1">Digunakan untuk menyelaraskan stok sistem dengan hasil penghitungan fisik opname (BR-16).</p>
                </div>
                <span class="px-2.5 py-1 bg-purple-100 text-purple-800 rounded font-semibold text-xs">Final (Tanpa Draft)</span>
            </div>

            <form method="POST" action="{{ route('adjustments.store') }}" class="space-y-6" @submit="onSubmit($event)">
                @csrf

                <!-- Hidden Input for Expected System Qty -->
                <input type="hidden" name="expected_system_qty" :value="systemStock">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Tanggal Penyesuaian -->
                    <div>
                        <label for="adjustment_date" class="block text-sm font-medium text-slate-700">
                            Tanggal Penyesuaian <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="date"
                            name="adjustment_date"
                            id="adjustment_date"
                            value="{{ old('adjustment_date', now()->toDateString()) }}"
                            required
                            max="{{ now()->toDateString() }}"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('adjustment_date') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        <p class="mt-1 text-xs text-slate-400">Maksimal tanggal lampau 7 hari (BR-20).</p>
                        @error('adjustment_date')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Alasan Penyesuaian -->
                    <div>
                        <label for="reason_code" class="block text-sm font-medium text-slate-700">
                            Alasan Selisih Opname <span class="text-rose-500">*</span>
                        </label>
                        <select
                            name="reason_code"
                            id="reason_code"
                            required
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('reason_code') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >
                            <option value="">Pilih Alasan...</option>
                            @foreach($reasons as $reason)
                                <option value="{{ $reason->value }}" {{ old('reason_code') === $reason->value ? 'selected' : '' }}>
                                    {{ $reason->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('reason_code')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Pemilihan Barang -->
                <div>
                    <label for="product_id" class="block text-sm font-medium text-slate-700">
                        Pilih Barang Persediaan <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="product_id"
                        id="product_id"
                        x-model="productId"
                        @change="onProductSelect()"
                        required
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('product_id') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    >
                        <option value="">Pilih barang yang disesuaikan...</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" {{ old('product_id') == $prod->id ? 'selected' : '' }}>
                                [{{ $prod->code }}] {{ $prod->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Perbandingan Stok: Sistem vs Fisik vs Selisih -->
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                        <!-- Stok Sistem (Readonly) -->
                        <div>
                            <span class="text-xs text-slate-500 font-medium block">Stok Sistem Saat Ini</span>
                            <div class="mt-1 font-mono text-base font-bold text-slate-800">
                                <span x-text="systemStock !== null ? formatStock(systemStock) : '—'"></span>
                                <span class="text-xs font-normal text-slate-500" x-text="unitSymbol"></span>
                            </div>
                        </div>

                        <!-- Stok Fisik (Input) -->
                        <div>
                            <label for="physical_qty" class="block text-xs text-slate-700 font-semibold">
                                Hasil Hitung Fisik <span class="text-rose-500">*</span>
                            </label>
                            <div class="mt-1 flex items-center space-x-1">
                                <input
                                    type="number"
                                    step="any"
                                    min="0"
                                    name="physical_qty"
                                    id="physical_qty"
                                    x-model="physicalQty"
                                    required
                                    placeholder="0"
                                    :disabled="productId === ''"
                                    class="w-full text-right text-sm font-mono rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100 disabled:cursor-not-allowed @error('physical_qty') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                                />
                                <span class="text-xs text-slate-500 font-medium w-8" x-text="unitSymbol"></span>
                            </div>
                        </div>

                        <!-- Selisih Terhitung (Preview) -->
                        <div>
                            <span class="text-xs text-slate-500 font-medium block">Selisih Penyesuaian</span>
                            <div class="mt-1 font-mono text-base font-bold" :class="diffClass()">
                                <span x-text="diffText()"></span>
                                <span class="text-xs font-normal text-slate-500" x-text="unitSymbol"></span>
                            </div>
                        </div>
                    </div>

                    <template x-if="systemStock !== null && physicalQty !== '' && calculateDiff() === 0">
                        <p class="text-xs text-amber-700 font-medium bg-amber-50 p-2 rounded-lg border border-amber-200">
                            Stok fisik sama dengan stok sistem; tidak ada yang perlu disesuaikan (NO_DIFFERENCE, AC-ADJ-04).
                        </p>
                    </template>

                    @error('physical_qty')
                        <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                    @error('expected_system_qty')
                        <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Keterangan / Notes (Wajib 10-1000) -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700">
                        Keterangan Detail Investigasi <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="3"
                        required
                        minlength="10"
                        maxlength="1000"
                        placeholder="Jelaskan penyebab selisih opname fisik (misal: barang rusak tertindih, salah hitung saat masuk, expired)..."
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('notes') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    >{{ old('notes') }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Minimal 10 karakter, maksimal 1000 karakter (BR-16).</p>
                    @error('notes')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('adjustments.index') }}"
                        class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        :disabled="productId === '' || physicalQty === '' || calculateDiff() === 0"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition disabled:opacity-50"
                    >
                        Posting Penyesuaian
                    </button>
                </div>
            </form>
        </div>

        <!-- Confirmation Modal -->
        <div
            x-show="confirmModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            x-cloak
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="confirmModalOpen = false"></div>

                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 relative z-10 border border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">Konfirmasi Penyesuaian Stok</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Apakah Anda yakin ingin memposting penyesuaian stok opname ini?
                    </p>
                    <div class="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg text-xs text-purple-900 space-y-1">
                        <p class="font-semibold">Informasi Penyesuaian (BR-16):</p>
                        <p>Stok sistem akan disesuaikan menjadi <strong class="font-mono" x-text="formatStock(physicalQty)"></strong> (<span x-text="diffText()"></span>).</p>
                        <p>Dokumen penyesuaian langsung bersifat final dan tidak dapat diubah maupun dibatalkan.</p>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="confirmModalOpen = false"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="proceedSubmit()"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition"
                        >
                            Ya, Posting Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function adjustmentForm() {
            return {
                productId: '{{ old('product_id', '') }}',
                systemStock: {{ old('expected_system_qty', 'null') }},
                physicalQty: '{{ old('physical_qty', '') }}',
                unitSymbol: '',
                confirmModalOpen: false,
                formEl: null,

                init() {
                    if (this.productId) {
                        this.fetchStock();
                    }
                },

                onProductSelect() {
                    this.fetchStock();
                },

                fetchStock() {
                    if (!this.productId) {
                        this.systemStock = null;
                        this.unitSymbol = '';
                        return;
                    }
                    fetch(`/ajax/products/${this.productId}/stock`)
                        .then(res => res.json())
                        .then(json => {
                            if (json.data) {
                                this.systemStock = parseFloat(json.data.current_stock);
                                this.unitSymbol = json.data.unit.symbol;
                            }
                        })
                        .catch(() => {});
                },

                calculateDiff() {
                    if (this.systemStock === null || this.physicalQty === '') return 0;
                    const p = parseFloat(this.physicalQty) || 0;
                    return p - this.systemStock;
                },

                diffText() {
                    if (this.systemStock === null || this.physicalQty === '') return '—';
                    const diff = this.calculateDiff();
                    const sign = diff > 0 ? '+' : '';
                    return sign + this.formatStock(diff);
                },

                diffClass() {
                    if (this.systemStock === null || this.physicalQty === '') return 'text-slate-400';
                    const diff = this.calculateDiff();
                    if (diff > 0) return 'text-emerald-600';
                    if (diff < 0) return 'text-rose-600';
                    return 'text-slate-500';
                },

                formatStock(val) {
                    return (parseFloat(val) || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
                },

                onSubmit(e) {
                    if (!this.confirmModalOpen) {
                        e.preventDefault();
                        this.formEl = e.target;
                        this.confirmModalOpen = true;
                    }
                },

                proceedSubmit() {
                    this.confirmModalOpen = false;
                    if (this.formEl) {
                        this.formEl.submit();
                    }
                }
            };
        }
    </script>
</x-app-layout>
