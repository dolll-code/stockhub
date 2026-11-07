<x-app-layout>
    <x-slot name="header">
        Tambah Satuan Barang
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-800">Formulir Satuan Baru</h2>
                <p class="text-sm text-slate-500 mt-1">Tambahkan satuan kuantitas barang persediaan (misal: pcs, kg, liter, meter).</p>
            </div>

            <form method="POST" action="{{ route('units.store') }}" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Nama Satuan -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700">
                            Nama Satuan <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            required
                            minlength="2"
                            maxlength="50"
                            placeholder="Contoh: Kilogram, Pieces, Liter"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('name') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('name')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Simbol Satuan -->
                    <div>
                        <label for="symbol" class="block text-sm font-medium text-slate-700">
                            Simbol / Singkatan <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="symbol"
                            id="symbol"
                            value="{{ old('symbol') }}"
                            required
                            minlength="1"
                            maxlength="10"
                            placeholder="Contoh: kg, pcs, L, box"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('symbol') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('symbol')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Tipe Kuantitas (allow_decimal) -->
                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Aturan Format Kuantitas <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="relative flex p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition border-slate-200">
                            <input
                                type="radio"
                                name="allow_decimal"
                                value="0"
                                {{ old('allow_decimal', '0') == '0' ? 'checked' : '' }}
                                class="mt-0.5 h-4 w-4 text-blue-600 border-slate-300 focus:ring-blue-500"
                            />
                            <div class="ml-3 text-sm">
                                <span class="font-semibold text-slate-800">Bilangan Bulat Saja</span>
                                <p class="text-xs text-slate-500 mt-0.5">Kuantitas tidak dapat memiliki desimal (mis. pcs, unit, box, karton).</p>
                            </div>
                        </label>

                        <label class="relative flex p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition border-slate-200">
                            <input
                                type="radio"
                                name="allow_decimal"
                                value="1"
                                {{ old('allow_decimal') == '1' ? 'checked' : '' }}
                                class="mt-0.5 h-4 w-4 text-blue-600 border-slate-300 focus:ring-blue-500"
                            />
                            <div class="ml-3 text-sm">
                                <span class="font-semibold text-slate-800">Mengizinkan Desimal</span>
                                <p class="text-xs text-slate-500 mt-0.5">Kuantitas dapat memiliki hingga 3 digit di belakang koma (mis. kg, gram, liter, meter).</p>
                            </div>
                        </label>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-400">Catatan: Pilihan ini terkunci permanen setelah satuan digunakan oleh barang (BR-25).</p>
                    @error('allow_decimal')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deskripsi -->
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">
                        Deskripsi (Opsional)
                    </label>
                    <textarea
                        name="description"
                        id="description"
                        rows="3"
                        maxlength="255"
                        placeholder="Keterangan standar pemakaian satuan..."
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('description') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    >{{ old('description') }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Maksimal 255 karakter.</p>
                    @error('description')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
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
                            <span class="font-medium text-slate-700">Satuan Aktif</span>
                            <p class="text-xs text-slate-500">Satuan aktif dapat langsung dipilih saat membuat atau mengubah master barang.</p>
                        </div>
                    </label>
                    @error('is_active')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('units.index') }}"
                        class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                    >
                        Simpan Satuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
