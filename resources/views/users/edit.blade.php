<x-app-layout>
    <x-slot name="header">
        Ubah Data Pengguna
    </x-slot>

    <div class="max-w-2xl bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm space-y-6">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Perbarui Informasi Akun [{{ $user->name }}]</h2>
            <p class="text-xs text-slate-500 mt-1">Pengubahan peran akan langsung berlaku pada request berikutnya. Pengguna tidak dapat mengubah peran akunnya sendiri (BR-24).</p>
        </div>

        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <x-input-label for="name" value="Nama Lengkap" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $user->name)" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email -->
            <div>
                <x-input-label for="email" value="Alamat Email" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $user->email)" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Role Selection -->
            <div>
                <x-input-label for="role" value="Peran / Hak Akses" />
                @php $currentRole = $user->roles->first()?->name; @endphp
                <select id="role" name="role" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" {{ Auth::id() === $user->id ? 'disabled' : '' }} required>
                    <option value="super_admin" {{ old('role', $currentRole) === 'super_admin' ? 'selected' : '' }}>Super Admin — Akses Penuh Sistem & Pengguna</option>
                    <option value="admin_gudang" {{ old('role', $currentRole) === 'admin_gudang' ? 'selected' : '' }}>Admin Gudang — Operasional Persediaan, Transaksi & Laporan</option>
                    <option value="staff_gudang" {{ old('role', $currentRole) === 'staff_gudang' ? 'selected' : '' }}>Staff Gudang — Pencatatan Transaksi Sendiri</option>
                    <option value="manajer" {{ old('role', $currentRole) === 'manajer' ? 'selected' : '' }}>Manajer — Pemantauan & Ekspor Laporan (Read-only)</option>
                </select>

                @if(Auth::id() === $user->id)
                    <input type="hidden" name="role" value="{{ $currentRole }}" />
                    <p class="mt-1 text-xs text-amber-600">Anda tidak dapat mengubah peran akun Anda sendiri.</p>
                @endif

                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
