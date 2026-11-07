<x-app-layout>
    <x-slot name="header">
        Detail Supplier: {{ $supplier->name }}
    </x-slot>

    <div class="space-y-6">
        <!-- Supplier Overview Card -->
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <span class="font-mono text-xs px-2.5 py-1 bg-blue-100 text-blue-800 rounded font-semibold">
                            {{ $supplier->code }}
                        </span>
                        <h2 class="text-xl font-bold text-slate-900">{{ $supplier->name }}</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $supplier->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                            {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">Terdaftar sejak {{ $supplier->created_at->isoFormat('D MMMM Y') }}</p>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="{{ route('suppliers.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition">
                        Kembali
                    </a>
                    @can('suppliers.manage')
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                            Ubah Data
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Detail Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-6 text-sm">
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block">Kontak PIC</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">{{ $supplier->contact_name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block">Nomor Telepon</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">{{ $supplier->phone ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block">Email</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">{{ $supplier->email ?? '—' }}</span>
                </div>
                <div class="md:col-span-2">
                    <span class="text-xs text-slate-400 uppercase tracking-wider block">Alamat</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">{{ $supplier->address ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block">Catatan Tambahan</span>
                    <span class="font-medium text-slate-800 mt-0.5 block">{{ $supplier->notes ?? '—' }}</span>
                </div>
            </div>
        </div>

        <!-- 10 Transaksi Masuk Terakhir (FR-SUP-04) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">10 Riwayat Transaksi Barang Masuk Terakhir</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Daftar transaksi penerimaan barang dari supplier ini.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                        <tr>
                            <th class="px-6 py-3">No. Dokumen</th>
                            <th class="px-6 py-3">Tanggal Transaksi</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Petugas</th>
                            <th class="px-6 py-3">Jumlah Item</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($recentStockIns as $stockIn)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-3.5 font-mono text-xs font-semibold text-blue-600">
                                    {{ $stockIn->document_number }}
                                </td>
                                <td class="px-6 py-3.5">
                                    {{ \Carbon\Carbon::parse($stockIn->transaction_date)->isoFormat('D MMMM Y') }}
                                </td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold">
                                        {{ $stockIn->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-slate-600">
                                    {{ $stockIn->creator?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-3.5 text-slate-600">
                                    {{ $stockIn->details->count() }} jenis barang
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Belum ada transaksi barang masuk dari supplier ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
