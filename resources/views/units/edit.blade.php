<x-app-layout>
    <x-slot name="header">
        Ubah Satuan: {{ $unit->name }} ({{ $unit->symbol }})
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Ubah Data Satuan</h2>
                    <p class="text-sm text-slate-500 mt-1">Perbarui nama, simbol, atau deskripsi satuan.</p>
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $unit->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                        {{ $unit->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('units.update', $unit) }}" class="space-y-6">
                @csrf
                @method('PUT')

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
                            value="{{ old('name', $unit->name) }}"
                            required
                            minlength="2"
                            maxlength="50"
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
                            value="{{ old('symbol', $unit->symbol) }}"
                            required
                            minlength="1"
                            maxlength="10"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('symbol') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('symbol')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Tipe Kuantitas (allow_decimal) -->
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-slate-700">
                            Aturan Format Kuantitas
                        </label>
                        @if($isLocked)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-amber-200">
                                Terkunci (Sudah Digunakan Barang)
                            </span>
                        @endif
                    </div>

                    @if($isLocked)
                        <!-- If locked, keep current value in hidden field -->
                        <input type="hidden" name="allow_decimal" value="{{ $unit->allow_decimal ? '1' : '0' }}">
                        <div class="mt-2 p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 flex items-center justify-between">
                            <div>
                                <span class="font-semibold">{{ $unit->allow_decimal ? 'Mengizinkan Desimal (Maks 3 digit)' : 'Bilangan Bulat Saja' }}</span>
                                <p class="text-xs text-slate-500 mt-0.5">Pengaturan desimal tidak dapat diubah karena satuan sudah digunakan pada master barang (BR-25).</p>
                            </div>
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                    @else
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="relative flex p-4 border rounded-xl cursor-pointer hover:bg-slate-50 transition border-slate-200">
                                <input
                                    type="radio"
                                    name="allow_decimal"
                                    value="0"
                                    {{ old('allow_decimal', $unit->allow_decimal ? '1' : '0') == '0' ? 'checked' : '' }}
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
                                    {{ old('allow_decimal', $unit->allow_decimal ? '1' : '0') == '1' ? 'checked' : '' }}
                                    class="mt-0.5 h-4 w-4 text-blue-600 border-slate-300 focus:ring-blue-500"
                                />
                                <div class="ml-3 text-sm">
                                    <span class="font-semibold text-slate-800">Mengizinkan Desimal</span>
                                    <p class="text-xs text-slate-500 mt-0.5">Kuantitas dapat memiliki hingga 3 digit di belakang koma (mis. kg, gram, liter, meter).</p>
                                </div>
                            </label>
                        </div>
                    @endif
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
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('description') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    >{{ old('description', $unit->description) }}</textarea>
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
                            {{ old('is_active', $unit->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <div class="ml-3 text-sm">
                            <span class="font-medium text-slate-700">Satuan Aktif</span>
                            <p class="text-xs text-slate-500">Nonaktifkan hanya jika satuan tidak lagi digunakan oleh barang aktif mana pun (BR-04).</p>
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
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
