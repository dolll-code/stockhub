<x-app-layout>
    <x-slot name="header">
        Detail Barang Keluar: {{ $stockOut->number }}
    </x-slot>

    <div class="space-y-6" x-data="{ cancelModalOpen: false, confirmModalOpen: false }">
        <!-- Overview Card -->
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <span class="font-mono text-sm px-3 py-1 bg-blue-100 text-blue-800 rounded font-bold">
                            {{ $stockOut->number }}
                        </span>
                        @php
                            $badgeClass = match($stockOut->status->value) {
                                'draft' => 'bg-amber-100 text-amber-800',
                                'confirmed' => 'bg-emerald-100 text-emerald-800',
                                'cancelled' => 'bg-slate-200 text-slate-700',
                                default => 'bg-slate-100 text-slate-800',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                            Status: {{ $stockOut->status->label() }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5">
                        Dibuat oleh {{ $stockOut->creator?->name ?? '—' }} pada {{ $stockOut->created_at->isoFormat('D MMMM Y, HH:mm') }}
                    </p>
                </div>

                <!-- Action Buttons Container -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('stock-outs.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition">
                        Kembali
                    </a>

                    @if($stockOut->status->value === 'draft')
                        @can('update', $stockOut)
                            <a href="{{ route('stock-outs.edit', $stockOut) }}" class="px-4 py-2 border border-blue-600 text-blue-600 hover:bg-blue-50 text-sm font-medium rounded-lg transition">
                                Ubah Draft
                            </a>
                        @endcan

                        @can('confirm', $stockOut)
                            <button
                                type="button"
                                @click="confirmModalOpen = true"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                            >
                                Konfirmasi Keluar
                            </button>
                        @endcan

                        @can('cancel', $stockOut)
                            <button
                                type="button"
                                @click="cancelModalOpen = true"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                            >
                                Batalkan Draft
                            </button>
                        @endcan
                    @elseif($stockOut->status->value === 'confirmed')
                        @can('cancel', $stockOut)
                            <button
                                type="button"
                                @click="cancelModalOpen = true"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-lg shadow-sm transition"
                            >
                                Batalkan Transaksi (Reversal)
                            </button>
                        @endcan
                    @endif
                </div>
            </div>

            <!-- Cancelled Notice Banner -->
            @if($stockOut->status->value === 'cancelled')
                <div class="mt-6 p-4 rounded-xl bg-slate-100 border border-slate-300 text-slate-800 text-sm">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-slate-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <span class="font-semibold">Dokumen Telah Dibatalkan (Final)</span>
                            <p class="text-xs text-slate-600 mt-0.5">
                                Dibatalkan oleh {{ $stockOut->canceller?->name ?? '—' }} pada {{ $stockOut->cancelled_at?->isoFormat('D MMMM Y, HH:mm') ?? '—' }}
                            </p>
                            <p class="text-xs font-medium text-slate-700 mt-1">
                                <strong>Alasan:</strong> {{ $stockOut->cancel_reason }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Confirmed Notice Banner -->
            @if($stockOut->status->value === 'confirmed')
                <div class="mt-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center space-x-3">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="font-semibold">Transaksi Telah Dikonfirmasi</span>
                        <p class="text-xs text-emerald-700 mt-0.5">
                            Dikonfirmasi oleh {{ $stockOut->confirmer?->name ?? '—' }} pada {{ $stockOut->confirmed_at?->isoFormat('D MMMM Y, HH:mm') ?? '—' }}. Stok barang telah dikurangkan dari persediaan.
                        </p>
                    </div>
                </div>
            @endif

            <!-- Metadata Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-6 text-sm">
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Tanggal Transaksi</span>
                    <span class="font-semibold text-slate-800 mt-0.5 block">
                        {{ $stockOut->transaction_date->isoFormat('D MMMM Y') }}
                    </span>
                </div>

                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Peruntukan Keluar</span>
                    <span class="font-semibold text-slate-800 mt-0.5 block">
                        {{ $stockOut->purpose_type->label() }}
                    </span>
                </div>

                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Tujuan / Penerima</span>
                    <span class="font-semibold text-slate-800 mt-0.5 block">
                        {{ $stockOut->destination }}
                    </span>
                </div>
            </div>

            @if($stockOut->notes)
                <div class="pt-6 border-t border-slate-100 mt-6 text-sm">
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Catatan</span>
                    <p class="text-slate-700 mt-0.5">{{ $stockOut->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Detail Items Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Daftar Barang Keluar</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $stockOut->details->count() }} baris item barang pada dokumen ini.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                        <tr>
                            <th class="px-6 py-3 w-8 text-center">#</th>
                            <th class="px-6 py-3">Kode & Nama Barang</th>
                            <th class="px-6 py-3 text-right">Kuantitas Keluar</th>
                            <th class="px-6 py-3">Satuan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($stockOut->details as $index => $detail)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 text-center text-xs text-slate-400">{{ $index + 1 }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs font-semibold text-blue-600">{{ $detail->product->code }}</div>
                                    <div class="font-medium text-slate-900">{{ $detail->product->name }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-900">
                                    {{ rtrim(rtrim(number_format((float) $detail->quantity, 3, ',', '.'), '0'), ',') }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 font-medium">
                                    {{ $detail->product->unit->symbol }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Confirm Confirmation Modal -->
        <div
            x-show="confirmModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            x-cloak
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="confirmModalOpen = false"></div>

                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 relative z-10 border border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">Konfirmasi Barang Keluar</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Apakah Anda yakin ingin mengonfirmasi pengeluaran dokumen <span class="font-bold font-mono text-slate-800">{{ $stockOut->number }}</span>?
                    </p>
                    <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800 space-y-1">
                        <p class="font-semibold">Efek Operasi Stok (BR-08, BR-10):</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>Stok persediaan seluruh item akan diverifikasi ketersediaannya dan langsung dikurangkan.</li>
                            <li>Bila ada satu barang yang stoknya kurang, konfirmasi ditolak sepenuhnya (tanpa pengurangan parsial).</li>
                            <li>Riwayat mutasi stok permanen (<code class="font-mono">stock_out</code>) akan tercatat.</li>
                        </ul>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3" x-data="{ submitting: false }">
                        <button
                            type="button"
                            @click="confirmModalOpen = false"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                            :disabled="submitting"
                        >
                            Batal
                        </button>
                        <form action="{{ route('stock-outs.confirm', $stockOut) }}" method="POST" @submit="submitting = true">
                            @csrf
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50"
                            >
                                <span x-show="!submitting">Ya, Konfirmasi Keluar</span>
                                <span x-show="submitting">Memproses...</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancellation Modal -->
        <div
            x-show="cancelModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            x-cloak
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="cancelModalOpen = false"></div>

                <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 relative z-10 border border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ $stockOut->status->value === 'confirmed' ? 'Batalkan Transaksi Keluar (Reversal)' : 'Batalkan Draft Barang Keluar' }}
                    </h3>
                    <p class="mt-2 text-sm text-slate-600">
                        @if($stockOut->status->value === 'confirmed')
                            Pembatalan transaksi confirmed akan membuat mutasi pembalik (<code class="font-mono font-semibold">stock_out_cancel</code>) dan mengembalikan stok barang ke gudang (BR-13).
                        @else
                            Draft barang keluar akan dibatalkan tanpa memengaruhi saldo stok (BR-14). Nomor dokumen tidak akan dipakai ulang.
                        @endif
                    </p>

                    <form action="{{ route('stock-outs.cancel', $stockOut) }}" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="cancel_reason" class="block text-sm font-medium text-slate-700">
                                Alasan Pembatalan <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                name="cancel_reason"
                                id="cancel_reason"
                                rows="3"
                                required
                                minlength="10"
                                maxlength="255"
                                placeholder="Jelaskan alasan pembatalan dokumen ini (minimal 10 karakter)..."
                                class="mt-1 block w-full text-sm rounded-lg border-slate-300 focus:border-rose-500 focus:ring-rose-500"
                            ></textarea>
                            <p class="mt-1 text-xs text-slate-400">Minimal 10 karakter, maksimal 255 karakter.</p>
                        </div>

                        <div class="flex justify-end space-x-3 pt-2">
                            <button
                                type="button"
                                @click="cancelModalOpen = false"
                                class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                            >
                                Kembali
                            </button>
                            <button
                                type="submit"
                                class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-medium rounded-lg transition"
                            >
                                Ya, Batalkan Dokumen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
