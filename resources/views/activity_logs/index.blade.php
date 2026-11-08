<x-app-layout>
    <x-slot name="header">
        Log Aktivitas Sistem
    </x-slot>

    <div class="space-y-6" x-data="{ detailModalOpen: false, activeLog: null }">
        <!-- Info Banner (BR-15, FR-LOG-01..03) -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 sm:p-5 flex items-start space-x-3 text-blue-900 text-sm">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <span class="font-bold">Audit Trail & Jejak Keamanan Sistem (Khusus Super Admin)</span>
                <p class="text-xs text-blue-800 mt-1">
                    Buku audit log mencatat seluruh aktivitas operasional dan keamanan penting secara permanen (append-only, BR-15). Data sensitif seperti kata sandi, token otentikasi, dan secret key otomatis disaring dan tidak pernah disimpan (FR-LOG-03).
                </p>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
                <!-- Search Input -->
                <div class="lg:col-span-2">
                    <label class="block text-slate-500 font-medium mb-1">Cari Keterangan / Aksi</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari kata kunci..."
                        class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Filter Aksi -->
                <div>
                    <label class="block text-slate-500 font-medium mb-1">Jenis Aksi</label>
                    <select name="action" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Aksi</option>
                        @foreach($distinctActions as $act)
                            <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                                {{ $act }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Petugas -->
                <div>
                    <label class="block text-slate-500 font-medium mb-1">Pengguna / Petugas</label>
                    <select name="user_id" class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Pengguna</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Rentang Tanggal -->
                <div>
                    <label class="block text-slate-500 font-medium mb-1">Dari Tanggal</label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label class="block text-slate-500 font-medium mb-1">Sampai Tanggal</label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        class="w-full text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Actions & Per Page -->
                <div class="sm:col-span-2 md:col-span-3 lg:col-span-6 flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                    <div class="flex items-center space-x-3">
                        <select name="per_page" class="text-xs rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15 baris</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 baris</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 baris</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 baris</option>
                        </select>

                        <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-lg transition">
                            Terapkan Filter
                        </button>

                        @if(request()->hasAny(['q', 'action', 'user_id', 'date_from', 'date_to', 'per_page']))
                            <a href="{{ route('activity-logs.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                                Reset
                            </a>
                        @endif
                    </div>

                    <span class="text-xs text-slate-400">
                        Total Log Terpantau: {{ number_format($logs->total(), 0, ',', '.') }}
                    </span>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                        <tr>
                            <th class="px-5 py-3.5">Waktu (WIB)</th>
                            <th class="px-5 py-3.5">Pengguna</th>
                            <th class="px-5 py-3.5">Aksi</th>
                            <th class="px-5 py-3.5">Keterangan</th>
                            <th class="px-5 py-3.5">IP & Perangkat</th>
                            <th class="px-5 py-3.5 text-right">Detail Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($logs as $log)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-5 py-3.5 text-xs text-slate-600 whitespace-nowrap font-mono">
                                    {{ $log->created_at->isoFormat('D/MM/Y HH:mm:ss') }}
                                </td>

                                <td class="px-5 py-3.5 text-xs">
                                    <div class="font-medium text-slate-900">{{ $log->user?->name ?? 'Sistem / Anonim' }}</div>
                                    @if($log->user)
                                        <div class="text-[10px] text-slate-400">{{ $log->user->email }}</div>
                                    @endif
                                </td>

                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ $log->action }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 text-xs text-slate-800 max-w-sm truncate" title="{{ $log->description }}">
                                    {{ $log->description }}
                                </td>

                                <td class="px-5 py-3.5 text-xs text-slate-500 whitespace-nowrap font-mono text-[11px]">
                                    <div>{{ $log->ip_address ?? '—' }}</div>
                                    <div class="text-[10px] text-slate-400 max-w-xs truncate" title="{{ $log->user_agent }}">
                                        {{ $log->user_agent ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    @if($log->old_values || $log->new_values)
                                        <button
                                            type="button"
                                            @click="activeLog = {{ json_encode($log) }}; detailModalOpen = true;"
                                            class="inline-flex items-center px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded text-xs font-medium transition"
                                        >
                                            Lihat Nilai
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                    Tidak ada rekam jejak aktivitas yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        <!-- Detail Modal (Alpine.js) -->
        <div
            x-show="detailModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            x-cloak
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="detailModalOpen = false"></div>

                <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 relative z-10 border border-slate-100 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Rincian Data Aktivitas</h3>
                            <p class="text-xs text-slate-500 font-mono" x-text="activeLog ? activeLog.action : ''"></p>
                        </div>
                        <button type="button" @click="detailModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="text-xs space-y-3" x-if="activeLog">
                        <div>
                            <span class="font-semibold text-slate-600 block">Keterangan:</span>
                            <p class="text-slate-800 bg-slate-50 p-2.5 rounded-lg border border-slate-100 font-medium" x-text="activeLog ? activeLog.description : ''"></p>
                        </div>

                        <!-- Side by Side Values -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Old Values -->
                            <div>
                                <span class="font-semibold text-slate-600 block mb-1">Nilai Sebelum (Old Values):</span>
                                <pre class="bg-slate-900 text-slate-100 p-3 rounded-lg overflow-x-auto text-[11px] font-mono leading-relaxed" x-text="activeLog && activeLog.old_values ? JSON.stringify(activeLog.old_values, null, 2) : 'Tidak ada data sebelum'"></pre>
                            </div>

                            <!-- New Values -->
                            <div>
                                <span class="font-semibold text-slate-600 block mb-1">Nilai Sesudah (New Values):</span>
                                <pre class="bg-slate-900 text-emerald-300 p-3 rounded-lg overflow-x-auto text-[11px] font-mono leading-relaxed" x-text="activeLog && activeLog.new_values ? JSON.stringify(activeLog.new_values, null, 2) : 'Tidak ada data sesudah'"></pre>
                            </div>
                        </div>

                        <div class="pt-2 text-[11px] text-slate-400 flex items-center justify-between border-t border-slate-100">
                            <span>Subjek: <code class="font-mono" x-text="activeLog ? (activeLog.subject_type + ' #' + activeLog.subject_id) : '—'"></code></span>
                            <span x-text="activeLog ? ('IP: ' + (activeLog.ip_address || '—')) : ''"></span>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button
                            type="button"
                            @click="detailModalOpen = false"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
