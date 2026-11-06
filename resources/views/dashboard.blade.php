<x-app-layout>
    <x-slot name="header">
        Ringkasan Sistem
    </x-slot>

    <div class="space-y-6">
        <!-- Status Box Tahap 0 -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex items-start space-x-3">
                <div class="p-2 bg-blue-600 rounded-lg text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-blue-900">Kerangka Aplikasi StockHub Berjalan (Tahap 0)</h3>
                    <p class="mt-1 text-sm text-blue-700">
                        Infrastruktur inti Laravel 12, Vite, Tailwind CSS, Alpine.js, Chart.js, SweetAlert2, Pest 3, Pint, dan Larastan siap digunakan.
                        Seluruh fitur bisnis saat ini berstatus <span class="font-bold underline">[BELUM DIIMPLEMENTASI]</span> sesuai rencana pengembangan MVP.
                    </p>
                </div>
            </div>
        </div>

        <!-- Metric Cards Skeleton -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-xs font-semibold text-slate-500 uppercase">Barang Tersedia</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">0</div>
                <div class="mt-1 text-xs text-slate-400">[BELUM DIIMPLEMENTASI]</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-xs font-semibold text-slate-500 uppercase">Stok Minimum</div>
                <div class="mt-2 text-2xl font-bold text-amber-600">0</div>
                <div class="mt-1 text-xs text-slate-400">[BELUM DIIMPLEMENTASI]</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-xs font-semibold text-slate-500 uppercase">Barang Habis</div>
                <div class="mt-2 text-2xl font-bold text-rose-600">0</div>
                <div class="mt-1 text-xs text-slate-400">[BELUM DIIMPLEMENTASI]</div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="text-xs font-semibold text-slate-500 uppercase">Transaksi Bulan Ini</div>
                <div class="mt-2 text-2xl font-bold text-blue-600">0</div>
                <div class="mt-1 text-xs text-slate-400">[BELUM DIIMPLEMENTASI]</div>
            </div>
        </div>
    </div>
</x-app-layout>
