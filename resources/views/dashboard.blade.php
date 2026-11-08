<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="space-y-6">
        <!-- Banner Rekonsiliasi untuk SA jika ditemukan selisih (FR-DSH-06) -->
        @if(!empty($hasReconciliationDisparity) && auth()->user()?->hasRole('super_admin'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start space-x-3">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <span class="font-semibold">Peringatan Integritas Saldo Persediaan (FR-SYS-01)</span>
                    <p class="text-xs text-rose-700 mt-0.5">
                        Rekonsiliasi sistem terakhir mendeteksi selisih antara saldo fisik berjalan dengan akumulasi riwayat mutasi stok. Lakukan penelusuran log aktivitas dan penyesuaian stok opname.
                    </p>
                </div>
            </div>
        @endif

        @if($isStaff)
            <!-- STAFF GUDANG DASHBOARD (FR-DSH-05: Limited to stock cards, critical products, and own recent transactions) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Barang Stok Tersedia -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Barang Stok Tersedia</span>
                    <div class="mt-2 text-3xl font-extrabold text-slate-900">
                        {{ number_format($availableStockProductsCount, 0, ',', '.') }}
                    </div>
                    <span class="mt-1 text-xs text-emerald-600 font-medium block">Stok saat ini &gt; 0</span>
                </div>

                <!-- Stok Minimum -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Stok Minimum</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    </div>
                    <div class="mt-2 text-3xl font-extrabold text-amber-600">
                        {{ number_format($lowStockProductsCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('stocks.low') }}" class="mt-1 text-xs text-amber-700 hover:underline block font-medium">
                        Perlu pengadaan &rarr;
                    </a>
                </div>

                <!-- Barang Habis -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider block">Barang Habis</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    </div>
                    <div class="mt-2 text-3xl font-extrabold text-rose-600">
                        {{ number_format($outOfStockProductsCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('stocks.low') }}" class="mt-1 text-xs text-rose-700 hover:underline block font-medium">
                        Stok kosong (0) &rarr;
                    </a>
                </div>
            </div>
        @else
            <!-- SA / AG / MG DASHBOARD (FR-DSH-01: Full 8 Summary Cards) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Barang Aktif -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Barang Aktif</span>
                    <div class="mt-2 text-2xl font-extrabold text-slate-900">
                        {{ number_format($activeProductsCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('products.index') }}" class="mt-1 text-xs text-blue-600 hover:underline block">
                        Kelola master barang &rarr;
                    </a>
                </div>

                <!-- 2. Kategori Aktif -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Kategori Aktif</span>
                    <div class="mt-2 text-2xl font-extrabold text-slate-900">
                        {{ number_format($activeCategoriesCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('categories.index') }}" class="mt-1 text-xs text-blue-600 hover:underline block">
                        Daftar kategori &rarr;
                    </a>
                </div>

                <!-- 3. Supplier Aktif -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Supplier Aktif</span>
                    <div class="mt-2 text-2xl font-extrabold text-slate-900">
                        {{ number_format($activeSuppliersCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('suppliers.index') }}" class="mt-1 text-xs text-blue-600 hover:underline block">
                        Daftar supplier &rarr;
                    </a>
                </div>

                <!-- 4. Barang Stok Tersedia (>0) BR-27 -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Barang Stok Tersedia</span>
                    <div class="mt-2 text-2xl font-extrabold text-emerald-600">
                        {{ number_format($availableStockProductsCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('stocks.index') }}?stock_status=aman" class="mt-1 text-xs text-emerald-700 hover:underline block font-medium">
                        Stok di atas 0 &rarr;
                    </a>
                </div>

                <!-- 5. Transaksi Masuk Bulan Ini (Confirmed) AC-DSH-01 -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Barang Masuk Bulan Ini</span>
                    <div class="mt-2 text-2xl font-extrabold text-cyan-700">
                        {{ number_format($monthlyStockInsCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('stock-ins.index') }}?status=confirmed" class="mt-1 text-xs text-cyan-800 hover:underline block font-medium">
                        Transaksi confirmed &rarr;
                    </a>
                </div>

                <!-- 6. Transaksi Keluar Bulan Ini (Confirmed) -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Barang Keluar Bulan Ini</span>
                    <div class="mt-2 text-2xl font-extrabold text-indigo-700">
                        {{ number_format($monthlyStockOutsCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('stock-outs.index') }}?status=confirmed" class="mt-1 text-xs text-indigo-800 hover:underline block font-medium">
                        Transaksi confirmed &rarr;
                    </a>
                </div>

                <!-- 7. Barang Stok Minimum -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Stok Minimum</span>
                    <div class="mt-2 text-2xl font-extrabold text-amber-600">
                        {{ number_format($lowStockProductsCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('stocks.low') }}" class="mt-1 text-xs text-amber-700 hover:underline block font-medium">
                        Perlu pemesanan &rarr;
                    </a>
                </div>

                <!-- 8. Barang Stok Habis -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider block">Barang Habis</span>
                    <div class="mt-2 text-2xl font-extrabold text-rose-600">
                        {{ number_format($outOfStockProductsCount, 0, ',', '.') }}
                    </div>
                    <a href="{{ route('stocks.low') }}" class="mt-1 text-xs text-rose-700 hover:underline block font-medium">
                        Stok bernilai 0 &rarr;
                    </a>
                </div>
            </div>

            <!-- GRAFIK TRANSAKSI CONFIRMED MASUK VS KELUAR (FR-DSH-02, BR-27: perbandingan jumlah transaksi confirmed) -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm" x-data="transactionChart()">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Perbandingan Transaksi Masuk vs Keluar</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Grafik membandingkan frekuensi transaksi confirmed per periode (BR-27).</p>
                    </div>

                    <!-- Period Selector -->
                    <div class="inline-flex items-center p-1 bg-slate-100 rounded-lg text-xs font-medium">
                        <button
                            type="button"
                            @click="changePeriod('daily')"
                            :class="period === 'daily' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-md transition"
                        >
                            Harian (14 Hari)
                        </button>
                        <button
                            type="button"
                            @click="changePeriod('weekly')"
                            :class="period === 'weekly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-md transition"
                        >
                            Mingguan (12 Minggu)
                        </button>
                        <button
                            type="button"
                            @click="changePeriod('monthly')"
                            :class="period === 'monthly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-md transition"
                        >
                            Bulanan (12 Bulan)
                        </button>
                    </div>
                </div>

                <!-- Canvas Area -->
                <div class="mt-4 relative" style="height: 320px;">
                    <div x-show="loading" class="absolute inset-0 bg-white/75 flex items-center justify-center z-10">
                        <span class="text-xs text-slate-500 font-medium">Memuat data grafik...</span>
                    </div>
                    <canvas id="dashboardTransactionChart"></canvas>
                </div>
            </div>
        @endif

        <!-- DUA KOLOM: 10 BARANG PALING KRITIS & 10 TRANSAKSI TERBARU -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- 1. Daftar 10 Barang Paling Kritis (FR-DSH-04) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">10 Barang Paling Kritis</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Barang habis didahulukan, diikuti rasio stok/minimum terendah.</p>
                    </div>
                    <a href="{{ route('stocks.low') }}" class="text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline">
                        Lihat Semua &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto flex-1">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-2.5">Barang</th>
                                <th class="px-4 py-2.5 text-right">Stok</th>
                                <th class="px-4 py-2.5 text-right">Min</th>
                                <th class="px-4 py-2.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($criticalProducts as $prod)
                                @php
                                    $status = $prod->stock_status;
                                    $badge = match($status->value) {
                                        'habis' => 'bg-rose-100 text-rose-800',
                                        'minimum' => 'bg-amber-100 text-amber-800',
                                        default => 'bg-slate-100 text-slate-800',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3">
                                        <div class="font-mono text-[11px] text-blue-600 font-semibold">{{ $prod->code }}</div>
                                        <div class="font-medium text-slate-900">{{ $prod->name }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold {{ (float) $prod->current_stock <= 0 ? 'text-rose-600' : 'text-slate-800' }}">
                                        {{ rtrim(rtrim(number_format((float) $prod->current_stock, 3, ',', '.'), '0'), ',') }}
                                        <span class="text-[10px] text-slate-400 font-normal">{{ $prod->unit->symbol }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-slate-500">
                                        {{ rtrim(rtrim(number_format((float) $prod->min_stock, 3, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badge }}">
                                            {{ $status->label() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                        Semua stok barang dalam kondisi aman (&gt; minimum).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Tabel 10 Transaksi Terbaru (FR-DSH-03: SA/AG/MG global, SG milik sendiri) -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">
                            {{ $isStaff ? '10 Transaksi Terbaru Anda' : '10 Transaksi Terbaru Sistem' }}
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Transaksi masuk dan keluar gabungan (semua status).</p>
                    </div>
                </div>

                <div class="overflow-x-auto flex-1">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase">
                            <tr>
                                <th class="px-4 py-2.5">No. Dokumen</th>
                                <th class="px-4 py-2.5">Jenis</th>
                                <th class="px-4 py-2.5">Tanggal</th>
                                <th class="px-4 py-2.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @forelse($recentTransactions as $tx)
                                @php
                                    $badge = match($tx['status']) {
                                        'draft' => 'bg-amber-100 text-amber-800',
                                        'confirmed' => 'bg-emerald-100 text-emerald-800',
                                        'cancelled' => 'bg-slate-200 text-slate-700',
                                        default => 'bg-slate-100 text-slate-800',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 font-mono font-medium text-blue-600">
                                        <a href="{{ $tx['url'] }}" class="hover:underline">
                                            {{ $tx['number'] }}
                                        </a>
                                        @if(!$isStaff)
                                            <div class="text-[10px] text-slate-400 font-sans">Oleh: {{ $tx['creator_name'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $tx['type'] === 'in' ? 'bg-cyan-100 text-cyan-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ $tx['type_label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                                        {{ $tx['date'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $badge }}">
                                            {{ $tx['status_label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                        Belum ada data transaksi yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(!$isStaff)
        <script>
            function transactionChart() {
                return {
                    period: 'daily',
                    loading: false,
                    chartInstance: null,

                    init() {
                        this.fetchData();
                    },

                    changePeriod(newPeriod) {
                        this.period = newPeriod;
                        this.fetchData();
                    },

                    fetchData() {
                        this.loading = true;
                        fetch(`/ajax/dashboard/chart?period=${this.period}`)
                            .then(res => res.json())
                            .then(data => {
                                this.renderChart(data);
                            })
                            .catch(err => {
                                console.error('Gagal memuat grafik transaksi:', err);
                            })
                            .finally(() => {
                                this.loading = false;
                            });
                    },

                    renderChart(data) {
                        const ctx = document.getElementById('dashboardTransactionChart');
                        if (!ctx || !window.Chart) return;

                        if (this.chartInstance) {
                            this.chartInstance.destroy();
                        }

                        this.chartInstance = new window.Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: data.labels,
                                datasets: [
                                    {
                                        label: 'Barang Masuk (Confirmed)',
                                        data: data.stock_in,
                                        backgroundColor: 'rgba(6, 182, 212, 0.75)',
                                        borderColor: 'rgb(6, 182, 212)',
                                        borderWidth: 1,
                                        borderRadius: 4,
                                    },
                                    {
                                        label: 'Barang Keluar (Confirmed)',
                                        data: data.stock_out,
                                        backgroundColor: 'rgba(244, 63, 94, 0.75)',
                                        borderColor: 'rgb(244, 63, 94)',
                                        borderWidth: 1,
                                        borderRadius: 4,
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                interaction: {
                                    intersect: false,
                                    mode: 'index',
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0,
                                            stepSize: 1,
                                        },
                                        title: {
                                            display: true,
                                            text: 'Jumlah Transaksi Confirmed',
                                            font: { size: 11 }
                                        }
                                    },
                                    x: {
                                        grid: { display: false }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                        labels: { font: { size: 12 } }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return `${context.dataset.label}: ${context.parsed.y} transaksi`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }
                };
            }
        </script>
    @endif
</x-app-layout>
