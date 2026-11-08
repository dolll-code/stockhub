<x-app-layout>
    <x-slot name="header">
        Transaksi Barang Masuk
    </x-slot>

    <div class="space-y-6">
        <!-- Toolbar & Filter -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <form method="GET" action="{{ route('stock-ins.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Search Nomor -->
                <div>
                    <input
                        type="text"
                        name="number"
                        value="{{ request('number') }}"
                        placeholder="Cari no. dokumen..."
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <!-- Sumber Filter -->
                <div>
                    <select name="source_type" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Sumber</option>
                        <option value="purchase" {{ request('source_type') === 'purchase' ? 'selected' : '' }}>Pembelian</option>
                        <option value="internal" {{ request('source_type') === 'internal' ? 'selected' : '' }}>Internal</option>
                        <option value="return" {{ request('source_type') === 'return' ? 'selected' : '' }}>Retur</option>
                        <option value="other" {{ request('source_type') === 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                <!-- Supplier Filter -->
                <div>
                    <select name="supplier_id" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tanggal Dari & Sampai -->
                <div>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        placeholder="Dari tanggal"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>
                <div>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        placeholder="Sampai tanggal"
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Per Page & Action -->
                <div class="sm:col-span-2 md:col-span-3 lg:col-span-6 flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                    <div class="flex items-center space-x-3">
                        <select name="per_page" class="text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15 baris</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 baris</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 baris</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 baris</option>
                        </select>

                        <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg transition">
                            Terapkan
                        </button>

                        @if(request()->hasAny(['number', 'status', 'source_type', 'supplier_id', 'date_from', 'date_to', 'per_page']))
                            <a href="{{ route('stock-ins.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                                Reset Filter
                            </a>
                        @endif
                    </div>

                    @can('stock-ins.create')
                        <div>
                            <a href="{{ route('stock-ins.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Buat Barang Masuk
                            </a>
                        </div>
                    @endcan
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                        <tr>
                            <th class="px-6 py-3.5">No. Dokumen</th>
                            <th class="px-6 py-3.5">Tanggal</th>
                            <th class="px-6 py-3.5">Sumber</th>
                            <th class="px-6 py-3.5">Supplier</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            @if($canViewPrice)
                                <th class="px-6 py-3.5 text-right">Total Nilai</th>
                            @endif
                            <th class="px-6 py-3.5">Petugas</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($stockIns as $stockIn)
                            @php
                                $statusBadgeClass = match($stockIn->status->value) {
                                    'draft' => 'bg-amber-100 text-amber-800',
                                    'confirmed' => 'bg-emerald-100 text-emerald-800',
                                    'cancelled' => 'bg-slate-200 text-slate-700',
                                    default => 'bg-slate-100 text-slate-800',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono font-semibold text-blue-600 text-xs">
                                    <a href="{{ route('stock-ins.show', $stockIn) }}" class="hover:underline">
                                        {{ $stockIn->number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 whitespace-nowrap">
                                    {{ $stockIn->transaction_date->isoFormat('D MMM Y') }}
                                </td>
                                <td class="px-6 py-4 text-xs font-medium text-slate-800">
                                    {{ $stockIn->source_type->label() }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 max-w-xs truncate">
                                    {{ $stockIn->supplier?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusBadgeClass }}">
                                        {{ $stockIn->status->label() }}
                                    </span>
                                </td>
                                @if($canViewPrice)
                                    <td class="px-6 py-4 text-right font-mono text-xs font-medium text-slate-900 whitespace-nowrap">
                                        @currency($stockIn->total_amount)
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    {{ $stockIn->creator?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('stock-ins.show', $stockIn) }}" class="text-slate-600 hover:text-slate-900 font-medium text-xs">
                                        Detail
                                    </a>

                                    @can('update', $stockIn)
                                        @if($stockIn->status->value === 'draft')
                                            <a href="{{ route('stock-ins.edit', $stockIn) }}" class="text-blue-600 hover:text-blue-900 font-medium text-xs">
                                                Ubah
                                            </a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canViewPrice ? 8 : 7 }}" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Tidak ada data transaksi barang masuk yang sesuai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($stockIns->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $stockIns->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
