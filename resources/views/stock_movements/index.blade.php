<x-app-layout>
    <x-slot name="header">
        Riwayat Mutasi Stok Persediaan
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Toolbar -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <form method="GET" action="{{ route('stock-movements.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                <!-- Search Barang -->
                <div class="lg:col-span-2">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari nama atau kode barang..."
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Filter Tipe Mutasi -->
                <div>
                    <select name="type" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Jenis Mutasi</option>
                        @foreach($movementTypes as $mt)
                            <option value="{{ $mt->value }}" {{ request('type') === $mt->value ? 'selected' : '' }}>
                                {{ $mt->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Pengguna -->
                <div>
                    <select name="user_id" class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Petugas</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
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
                <div class="sm:col-span-2 md:col-span-4 lg:col-span-6 flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                    <div class="flex items-center space-x-3">
                        <select name="per_page" class="text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15 baris</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 baris</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 baris</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 baris</option>
                        </select>

                        <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-medium rounded-lg transition">
                            Terapkan Filter
                        </button>

                        @if(request()->hasAny(['q', 'type', 'user_id', 'date_from', 'date_to', 'per_page']))
                            <a href="{{ route('stock-movements.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                                Reset Filter
                            </a>
                        @endif
                    </div>

                    <span class="text-xs text-slate-400">
                        Buku besar mutasi bersifat tetap dan append-only (BR-15).
                    </span>
                </div>
            </form>
        </div>

        <!-- Movements Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                        <tr>
                            <th class="px-5 py-3.5">Waktu & Tanggal</th>
                            <th class="px-5 py-3.5">Barang</th>
                            <th class="px-5 py-3.5">Jenis Mutasi</th>
                            <th class="px-5 py-3.5">No. Referensi Dokumen</th>
                            <th class="px-5 py-3.5 text-right">Saldo Sebelum</th>
                            <th class="px-5 py-3.5 text-right">Perubahan</th>
                            <th class="px-5 py-3.5 text-right">Saldo Sesudah</th>
                            <th class="px-5 py-3.5">Petugas</th>
                            <th class="px-5 py-3.5">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($movements as $m)
                            @php
                                $typeVal = $m->movement_type->value ?? $m->movement_type;
                                $badgeClass = match($typeVal) {
                                    'opening_balance' => 'bg-purple-100 text-purple-800',
                                    'stock_in' => 'bg-emerald-100 text-emerald-800',
                                    'stock_out' => 'bg-blue-100 text-blue-800',
                                    'adjustment' => 'bg-amber-100 text-amber-800',
                                    'stock_in_cancel' => 'bg-rose-100 text-rose-800',
                                    'stock_out_cancel' => 'bg-teal-100 text-teal-800',
                                    default => 'bg-slate-100 text-slate-800',
                                };

                                $docRoute = null;
                                if ($m->reference_type && $m->reference_id) {
                                    if (str_contains($m->reference_type, 'StockIn')) {
                                        $docRoute = route('stock-ins.show', $m->reference_id);
                                    } elseif (str_contains($m->reference_type, 'StockOut')) {
                                        $docRoute = route('stock-outs.show', $m->reference_id);
                                    } elseif (str_contains($m->reference_type, 'StockAdjustment')) {
                                        $docRoute = route('adjustments.show', $m->reference_id);
                                    }
                                }
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-5 py-3.5 text-xs text-slate-600 whitespace-nowrap">
                                    <div>{{ $m->created_at->isoFormat('D MMM Y, HH:mm') }}</div>
                                    <div class="text-[10px] text-slate-400">Tgl Bisnis: {{ $m->movement_date->isoFormat('D/MM/Y') }}</div>
                                </td>

                                <td class="px-5 py-3.5">
                                    <div class="font-mono text-xs font-semibold text-blue-600">{{ $m->product->code }}</div>
                                    <a href="{{ route('products.show', $m->product) }}" class="font-medium text-slate-900 hover:text-blue-600 hover:underline">
                                        {{ $m->product->name }}
                                    </a>
                                </td>

                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $badgeClass }}">
                                        {{ $m->movement_type->label() }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 font-mono text-xs">
                                    @if($docRoute)
                                        <a href="{{ $docRoute }}" class="text-blue-600 hover:underline font-semibold">
                                            {{ $m->reference_number ?? 'Dokumen' }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">{{ $m->reference_number ?? '—' }}</span>
                                    @endif
                                </td>

                                <td class="px-5 py-3.5 text-right font-mono text-xs text-slate-500">
                                    {{ rtrim(rtrim(number_format((float) $m->qty_before, 3, ',', '.'), '0'), ',') }}
                                </td>

                                <td class="px-5 py-3.5 text-right font-mono text-xs font-bold {{ (float) $m->qty_change > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ (float) $m->qty_change > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $m->qty_change, 3, ',', '.'), '0'), ',') }}
                                    <span class="text-[10px] font-normal text-slate-400">{{ $m->product->unit->symbol }}</span>
                                </td>

                                <td class="px-5 py-3.5 text-right font-mono text-xs font-bold text-slate-900">
                                    {{ rtrim(rtrim(number_format((float) $m->qty_after, 3, ',', '.'), '0'), ',') }}
                                </td>

                                <td class="px-5 py-3.5 text-xs text-slate-600">
                                    {{ $m->user?->name ?? 'Sistem' }}
                                </td>

                                <td class="px-5 py-3.5 text-xs text-slate-500 max-w-xs truncate" title="{{ $m->notes }}">
                                    {{ $m->notes ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                    Tidak ada catatan mutasi riwayat stok yang sesuai.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
