<x-app-layout>
    <x-slot name="header">
        Tambah Kategori Barang
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-800">Formulir Kategori Baru</h2>
                <p class="text-sm text-slate-500 mt-1">Tambahkan kategori untuk pengelompokan master barang persediaan.</p>
            </div>

            <form method="POST" action="{{ route('categories.store') }}" class="space-y-6">
                @csrf

                <!-- Nama Kategori -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">
                        Nama Kategori <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        required
                        minlength="3"
                        maxlength="100"
                        placeholder="Contoh: Elektronik, Bahan Baku, Alat Tulis"
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('name') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    />
                    <p class="mt-1 text-xs text-slate-400">Minimal 3 karakter, maksimal 100 karakter. Nama kategori bersifat unik (case-insensitive).</p>
                    @error('name')
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
                        maxlength="500"
                        placeholder="Keterangan singkat mengenai kelompok kategori barang ini..."
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('description') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    >{{ old('description') }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Maksimal 500 karakter.</p>
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
                            <span class="font-medium text-slate-700">Kategori Aktif</span>
                            <p class="text-xs text-slate-500">Kategori aktif dapat langsung dipilih saat membuat atau mengubah master barang.</p>
                        </div>
                    </label>
                    @error('is_active')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('categories.index') }}"
                        class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                    >
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
