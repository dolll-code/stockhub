<x-app-layout>
    <x-slot name="header">
        Pengaturan Sistem
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6" x-data="{ logoPreview: null, removeLogo: false }">
        <!-- Info Banner (FR-SET-01, FR-SET-02) -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 sm:p-5 flex items-start space-x-3 text-blue-900 text-sm">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <div>
                <span class="font-bold">Konfigurasi Pengaturan Sistem (Khusus Super Admin)</span>
                <p class="text-xs text-blue-800 mt-1">
                    Informasi nama perusahaan, alamat, telepon, dan logo digunakan pada kop resmi dokumen ekspor PDF. Batas tanggal lampau (max backdate days) mengatur toleransi maksimal tanggal lampau yang diizinkan saat mencatat transaksi barang dan opname.
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-800">Identitas Perusahaan & Kebijakan Transaksi</h2>
                <p class="text-sm text-slate-500 mt-0.5">Perubahan konfigurasi akan dicatat ke dalam audit log aktivitas.</p>
            </div>

            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Nama Perusahaan -->
                <div>
                    <label for="company_name" class="block text-sm font-medium text-slate-700">
                        Nama Perusahaan / Organisasi <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="company_name"
                        id="company_name"
                        value="{{ old('company_name', $settings['company_name']) }}"
                        required
                        minlength="3"
                        maxlength="150"
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('company_name') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    />
                    <p class="mt-1 text-xs text-slate-400">Ditampilkan pada judul kop dokumen laporan PDF.</p>
                    @error('company_name')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Alamat Perusahaan -->
                <div>
                    <label for="company_address" class="block text-sm font-medium text-slate-700">
                        Alamat Perusahaan (Opsional)
                    </label>
                    <textarea
                        name="company_address"
                        id="company_address"
                        rows="3"
                        maxlength="500"
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('company_address') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    >{{ old('company_address', $settings['company_address']) }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Alamat kantor atau fasilitas gudang utama.</p>
                    @error('company_address')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Telepon Perusahaan -->
                <div>
                    <label for="company_phone" class="block text-sm font-medium text-slate-700">
                        Nomor Telepon Kantor (Opsional)
                    </label>
                    <input
                        type="text"
                        name="company_phone"
                        id="company_phone"
                        value="{{ old('company_phone', $settings['company_phone']) }}"
                        maxlength="30"
                        placeholder="Contoh: (021) 555-1234 atau 08123456789"
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('company_phone') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    />
                    @error('company_phone')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Batas Backdate Days (BR-20) -->
                <div>
                    <label for="max_backdate_days" class="block text-sm font-medium text-slate-700">
                        Batas Tanggal Lampau Transaksi (Hari) <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-1 flex items-center space-x-3">
                        <input
                            type="number"
                            name="max_backdate_days"
                            id="max_backdate_days"
                            value="{{ old('max_backdate_days', $settings['max_backdate_days']) }}"
                            min="0"
                            max="30"
                            required
                            class="w-32 text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('max_backdate_days') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        <span class="text-xs text-slate-500">hari yang lalu (Rentang: 0 s.d. 30 hari; default 7 hari)</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">
                        Mencegah input transaksi yang terlalu lampau. Jika diatur 0, transaksi hanya boleh bertanggal hari ini.
                    </p>
                    @error('max_backdate_days')
                        <p class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Logo Perusahaan (FR-SET-02) -->
                <div class="pt-2 border-t border-slate-100">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Logo Perusahaan untuk Kop PDF (Opsional)
                    </label>

                    <div class="flex items-start space-x-6">
                        <!-- Preview Box -->
                        <div class="w-24 h-24 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                            <template x-if="logoPreview">
                                <img :src="logoPreview" class="w-full h-full object-contain p-1" />
                            </template>
                            <template x-if="!logoPreview && !removeLogo && '{{ $settings['company_logo_path'] }}'">
                                <img src="{{ asset('storage/'.$settings['company_logo_path']) }}" class="w-full h-full object-contain p-1" />
                            </template>
                            <template x-if="!logoPreview && (removeLogo || !'{{ $settings['company_logo_path'] }}')">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </template>
                        </div>

                        <!-- Upload & Controls -->
                        <div class="flex-1 space-y-2">
                            <input
                                type="file"
                                name="company_logo"
                                id="company_logo"
                                accept="image/png,image/jpeg,image/jpg"
                                @change="
                                    const file = $event.target.files[0];
                                    if (file) {
                                        removeLogo = false;
                                        const reader = new FileReader();
                                        reader.onload = (e) => { logoPreview = e.target.result; };
                                        reader.readAsDataURL(file);
                                    } else {
                                        logoPreview = null;
                                    }
                                "
                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                            />
                            <p class="text-xs text-slate-400">Format file: PNG, JPG, JPEG. Ukuran file maksimal 1 MB (1024 KB).</p>

                            @if($settings['company_logo_path'])
                                <div class="pt-1">
                                    <label class="inline-flex items-center text-xs text-rose-600 cursor-pointer font-medium">
                                        <input
                                            type="checkbox"
                                            name="remove_logo"
                                            value="1"
                                            @change="removeLogo = $event.target.checked; if ($event.target.checked) logoPreview = null;"
                                            class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 h-3.5 w-3.5 mr-1.5"
                                        />
                                        Hapus logo yang saat ini terpasang
                                    </label>
                                </div>
                            @endif

                            @error('company_logo')
                                <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end">
                    <button
                        type="submit"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition"
                    >
                        Simpan Pengaturan Sistem
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
