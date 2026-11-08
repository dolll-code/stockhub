<x-app-layout>
    <x-slot name="header">
        Penyesuaian Stok Opname
    </x-slot>

    <div class="space-y-6">
        <!-- Toolbar & Filter -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <form method="GET" action="{{ route('adjustments.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <!-- Filter Barang -->
                <div>
                    <select name="product_id" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Barang</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                                [{{ $prod->code }}] {{ $prod->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Alasan -->
                <div>
                    <select name="reason_code" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Alasan</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason->value }}" {{ request('reason_code') === $reason->value ? 'selected' : '' }}>
                                {{ $reason->label() }}
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

                <!-- Actions & Per Page -->
                <div class="sm:col-span-2 md:col-span-3 lg:col-span-5 flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
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

                        @if(request()->hasAny(['product_id', 'reason_code', 'date_from', 'date_to', 'per_page']))
                            <a href="{{ route('adjustments.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                                Reset Filter
                            </a>
                        @endif
                    </div>

                    @can('adjustments.create')
                        <div>
                            <a href="{{ route('adjustments.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Penyesuaian Stok Baru
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
                            <th class="px-6 py-3.5">Barang</th>
                            <th class="px-6 py-3.5 text-right">Stok Sistem</th>
                            <th class="px-6 py-3.5 text-right">Stok Fisik</th>
                            <th class="px-6 py-3.5 text-right">Selisih</th>
                            <th class="px-6 py-3.5">Alasan</th>
                            <th class="px-6 py-3.5">Petugas</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($adjustments as $adj)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-mono font-semibold text-blue-600 text-xs">
                                    <a href="{{ route('adjustments.show', $adj) }}" class="hover:underline">
                                        {{ $adj->number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 whitespace-nowrap">
                                    {{ $adj->adjustment_date->isoFormat('D MMM Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs font-semibold text-slate-700">{{ $adj->product->code }}</div>
                                    <div class="font-medium text-slate-900 text-xs">{{ $adj->product->name }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-xs text-slate-600">
                                    {{ rtrim(rtrim(number_format((float) $adj->system_qty, 3, ',', '.'), '0'), ',') }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-xs font-semibold text-slate-800">
                                    {{ rtrim(rtrim(number_format((float) $adj->physical_qty, 3, ',', '.'), '0'), ',') }}
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-xs font-bold {{ (float) $adj->difference > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ (float) $adj->difference > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $adj->difference, 3, ',', '.'), '0'), ',') }}
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ $adj->reason_code->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    {{ $adj->creator?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('adjustments.show', $adj) }}" class="text-slate-600 hover:text-slate-900 font-medium text-xs">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Tidak ada data penyesuaian stok opname.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($adjustments->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $adjustments->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
