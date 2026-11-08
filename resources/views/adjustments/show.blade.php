<x-app-layout>
    <x-slot name="header">
        Detail Penyesuaian: {{ $adjustment->number }}
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white p-6 sm:p-8 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-100 gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <span class="font-mono text-sm px-3 py-1 bg-purple-100 text-purple-800 rounded font-bold">
                            {{ $adjustment->number }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                            Status: Final
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1.5">
                        Dibuat oleh {{ $adjustment->creator?->name ?? '—' }} pada {{ $adjustment->created_at->isoFormat('D MMMM Y, HH:mm') }}
                    </p>
                </div>

                <div>
                    <a href="{{ route('adjustments.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition">
                        Kembali
                    </a>
                </div>
            </div>

            <!-- Detail Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-6 text-sm">
                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Tanggal Penyesuaian</span>
                    <span class="font-semibold text-slate-800 mt-0.5 block">
                        {{ $adjustment->adjustment_date->isoFormat('D MMMM Y') }}
                    </span>
                </div>

                <div>
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Alasan Penyesuaian</span>
                    <span class="font-semibold text-slate-800 mt-0.5 block">
                        {{ $adjustment->reason_code->label() }}
                    </span>
                </div>

                <div class="sm:col-span-2 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Barang yang Disesuaikan</span>
                    <div class="mt-1 flex items-center justify-between">
                        <div>
                            <div class="font-mono text-xs font-semibold text-blue-600">{{ $adjustment->product->code }}</div>
                            <div class="text-base font-bold text-slate-900">{{ $adjustment->product->name }}</div>
                        </div>
                        <a href="{{ route('products.show', $adjustment->product) }}" class="text-xs text-blue-600 hover:underline">
                            Lihat Kartu Stok &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-3 gap-4 pt-4 mt-4 border-t border-slate-200 text-center">
                        <div>
                            <span class="text-[11px] text-slate-400 uppercase block font-medium">Stok Sistem Saat Penyesuaian</span>
                            <span class="font-mono text-base font-bold text-slate-700 mt-0.5 block">
                                {{ rtrim(rtrim(number_format((float) $adjustment->system_qty, 3, ',', '.'), '0'), ',') }}
                                <span class="text-xs font-normal text-slate-400">{{ $adjustment->product->unit->symbol }}</span>
                            </span>
                        </div>

                        <div>
                            <span class="text-[11px] text-slate-400 uppercase block font-medium">Hasil Hitung Fisik</span>
                            <span class="font-mono text-base font-bold text-slate-900 mt-0.5 block">
                                {{ rtrim(rtrim(number_format((float) $adjustment->physical_qty, 3, ',', '.'), '0'), ',') }}
                                <span class="text-xs font-normal text-slate-400">{{ $adjustment->product->unit->symbol }}</span>
                            </span>
                        </div>

                        <div>
                            <span class="text-[11px] text-slate-400 uppercase block font-medium">Selisih Penyesuaian</span>
                            <span class="font-mono text-base font-bold mt-0.5 block {{ (float) $adjustment->difference > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ (float) $adjustment->difference > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $adjustment->difference, 3, ',', '.'), '0'), ',') }}
                                <span class="text-xs font-normal text-slate-400">{{ $adjustment->product->unit->symbol }}</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <span class="text-xs text-slate-400 uppercase tracking-wider block font-medium">Keterangan Investigasi Selisih</span>
                    <p class="mt-1 text-slate-800 leading-relaxed bg-slate-50 p-3 rounded-lg border border-slate-100">
                        {{ $adjustment->notes }}
                    </p>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 text-xs text-slate-400 flex items-center justify-between">
                <span>Dokumen penyesuaian stok opname bersifat permanen dan tidak dapat diubah (BR-16).</span>
                <span class="font-mono">Ref: {{ $adjustment->number }}</span>
            </div>
        </div>
    </div>
</x-app-layout>
