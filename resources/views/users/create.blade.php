<x-app-layout>
    <x-slot name="header">
        Tambah Pengguna Baru
    </x-slot>

    <div class="max-w-2xl bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-6">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Formulir Pendaftaran Akun</h2>
            <p class="text-xs text-slate-500 mt-1">Akun yang dibuat akan memiliki status aktif dan wajib mengganti kata sandi sementara saat pertama kali masuk (FR-USR-02).</p>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <x-input-label for="name" value="Nama Lengkap" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus placeholder="Contoh: Ahmad Fauzi" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email -->
            <div>
                <x-input-label for="email" value="Alamat Email" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required placeholder="nama@perusahaan.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Role Selection -->
            <div>
                <x-input-label for="role" value="Peran / Hak Akses" />
                <select id="role" name="role" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Pilih Peran...</option>
                    <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin — Akses Penuh Sistem & Pengguna</option>
                    <option value="admin_gudang" {{ old('role') === 'admin_gudang' ? 'selected' : '' }}>Admin Gudang — Operasional Persediaan, Transaksi & Laporan</option>
                    <option value="staff_gudang" {{ old('role') === 'staff_gudang' ? 'selected' : '' }}>Staff Gudang — Pencatatan Transaksi Sendiri</option>
                    <option value="manajer" {{ old('role') === 'manajer' ? 'selected' : '' }}>Manajer — Pemantauan & Ekspor Laporan (Read-only)</option>
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <!-- Temporary Password -->
            <div>
                <x-input-label for="password" value="Kata Sandi Sementara" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required placeholder="Minimal 10 karakter (huruf besar, kecil, angka)" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-sm transition">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
