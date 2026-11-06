<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-slate-800">Ganti Kata Sandi Sementara</h2>
        <p class="text-xs text-slate-500 mt-1">Anda diwajibkan menetapkan kata sandi baru sebelum dapat menggunakan aplikasi StockHub.</p>
    </div>

    @if (session('warning'))
        <div class="mb-4 p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs rounded-lg">
            {{ session('warning') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf
        @method('PUT')

        <!-- Password Baru -->
        <div>
            <x-input-label for="password" value="Kata Sandi Baru" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password"
                            placeholder="Minimal 10 karakter (huruf besar, kecil, angka)" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Konfirmasi Password Baru -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi Baru" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password"
                            placeholder="Ulangi kata sandi baru" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between">
            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium text-sm rounded-lg shadow-sm transition">
                Simpan Kata Sandi Baru
            </button>
        </div>
    </form>

    <div class="mt-4 pt-4 border-t border-slate-200 text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs text-slate-500 hover:text-slate-800 underline">
                Batal dan Keluar (Logout)
            </button>
        </form>
    </div>
</x-guest-layout>
