<x-app-layout>
    <x-slot name="header">
        Ubah Supplier: {{ $supplier->name }}
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="mb-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Ubah Data Supplier</h2>
                    <p class="text-sm text-slate-500 mt-1">Perbarui data kontak, alamat, atau status operasional supplier.</p>
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $supplier->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                        {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Kode Supplier (Immutable BR-05) -->
                <div>
                    <label class="block text-sm font-medium text-slate-700">
                        Kode Supplier
                    </label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input
                            type="text"
                            value="{{ $supplier->code }}"
                            disabled
                            class="bg-slate-100 text-slate-500 cursor-not-allowed font-mono text-sm rounded-lg border-slate-300 w-full sm:w-64"
                        />
                        <span class="text-xs text-slate-400">Kode supplier bersifat tetap dan tidak dapat diubah (immutable).</span>
                    </div>
                </div>

                <!-- Nama Supplier -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">
                        Nama Supplier / Perusahaan <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name', $supplier->name) }}"
                        required
                        minlength="3"
                        maxlength="150"
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('name') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    />
                    @error('name')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- Kontak PIC -->
                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-slate-700">
                            Nama Kontak (PIC)
                        </label>
                        <input
                            type="text"
                            name="contact_name"
                            id="contact_name"
                            value="{{ old('contact_name', $supplier->contact_name) }}"
                            maxlength="100"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('contact_name') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('contact_name')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nomor Telepon -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700">
                            Nomor Telepon
                        </label>
                        <input
                            type="text"
                            name="phone"
                            id="phone"
                            value="{{ old('phone', $supplier->phone) }}"
                            minlength="6"
                            maxlength="30"
                            class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('phone') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        <p class="mt-1 text-xs text-slate-400">Hanya angka, spasi, +, (), dan -.</p>
                        @error('phone')
                            <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">
                        Email
                    </label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email', $supplier->email) }}"
                        maxlength="150"
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('email') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    />
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Alamat -->
                <div>
                    <label for="address" class="block text-sm font-medium text-slate-700">
                        Alamat
                    </label>
                    <textarea
                        name="address"
                        id="address"
                        rows="3"
                        maxlength="1000"
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('address') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    >{{ old('address', $supplier->address) }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Maksimal 1000 karakter.</p>
                    @error('address')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700">
                        Catatan Tambahan (Opsional)
                    </label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="2"
                        maxlength="1000"
                        class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 @error('notes') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    >{{ old('notes', $supplier->notes) }}</textarea>
                    <p class="mt-1 text-xs text-slate-400">Maksimal 1000 karakter.</p>
                    @error('notes')
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
                            {{ old('is_active', $supplier->is_active ? '1' : '0') == '1' ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <div class="ml-3 text-sm">
                            <span class="font-medium text-slate-700">Supplier Aktif</span>
                            <p class="text-xs text-slate-500">Supplier dapat dinonaktifkan kapan saja (BR-04). Supplier nonaktif tidak dapat dipilih di transaksi baru.</p>
                        </div>
                    </label>
                    @error('is_active')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3">
                    <a
                        href="{{ route('suppliers.index') }}"
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
