<aside
    class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-200 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    @click.away="sidebarOpen = false"
>
    <!-- Brand Header -->
    <div class="flex items-center justify-between h-16 px-6 bg-slate-950 border-b border-slate-800">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
            <span class="p-2 bg-blue-600 rounded-lg text-white font-bold text-lg leading-none">SH</span>
            <span class="font-bold text-lg text-white tracking-wide">StockHub</span>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Navigation Menu (PRD §13.1 Baseline Skeleton) -->
    <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto text-sm">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 rounded-lg text-white font-medium bg-blue-600 hover:bg-blue-700 transition">
            <svg class="w-5 h-5 mr-3 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span>Dashboard</span>
        </a>

        <!-- Master Data -->
        @canany(['products.view', 'products.manage', 'categories.view', 'categories.manage', 'units.view', 'units.manage', 'suppliers.view', 'suppliers.manage'])
            <div class="pt-2">
                <div class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Master Data</div>
                <div class="mt-1 space-y-1">
                    @canany(['products.view', 'products.manage'])
                        <a href="{{ route('products.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('products.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-emerald-500 rounded-full"></span>
                            <span>Barang</span>
                        </a>
                    @endcanany

                    @canany(['categories.view', 'categories.manage'])
                        <a href="{{ route('categories.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('categories.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-indigo-500 rounded-full"></span>
                            <span>Kategori</span>
                        </a>
                    @endcanany

                    @canany(['units.view', 'units.manage'])
                        <a href="{{ route('units.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('units.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-amber-500 rounded-full"></span>
                            <span>Satuan</span>
                        </a>
                    @endcanany

                    @canany(['suppliers.view', 'suppliers.manage'])
                        <a href="{{ route('suppliers.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('suppliers.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-teal-500 rounded-full"></span>
                            <span>Supplier</span>
                        </a>
                    @endcanany
                </div>
            </div>
        @endcanany

        <!-- Transaksi -->
        @canany(['stock-ins.view', 'stock-ins.create', 'stock-outs.view', 'stock-outs.create'])
            <div class="pt-2">
                <div class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Transaksi</div>
                <div class="mt-1 space-y-1">
                    @canany(['stock-ins.view', 'stock-ins.create'])
                        <a href="{{ route('stock-ins.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('stock-ins.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-cyan-500 rounded-full"></span>
                            <span>Barang Masuk</span>
                        </a>
                    @endcanany

                    @canany(['stock-outs.view', 'stock-outs.create'])
                        <a href="{{ route('stock-outs.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('stock-outs.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-rose-500 rounded-full"></span>
                            <span>Barang Keluar</span>
                        </a>
                    @endcanany
                </div>
            </div>
        @endcanany

        <!-- Persediaan -->
        @canany(['stocks.view', 'adjustments.view', 'adjustments.create', 'stock-movements.view'])
            <div class="pt-2">
                <div class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Persediaan</div>
                <div class="mt-1 space-y-1">
                    @can('stocks.view')
                        <a href="{{ route('stocks.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('stocks.index') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-emerald-500 rounded-full"></span>
                            <span>Stok Barang</span>
                        </a>

                        <a href="{{ route('stocks.low') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('stocks.low') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-amber-500 rounded-full"></span>
                            <span>Stok Minimum</span>
                        </a>
                    @endcan

                    @canany(['adjustments.view', 'adjustments.create'])
                        <a href="{{ route('adjustments.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('adjustments.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-purple-500 rounded-full"></span>
                            <span>Penyesuaian</span>
                        </a>
                    @endcanany

                    @can('stock-movements.view')
                        <a href="{{ route('stock-movements.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('stock-movements.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-blue-500 rounded-full"></span>
                            <span>Riwayat Stok</span>
                        </a>
                    @endcan
                </div>
            </div>
        @endcanany

        <!-- Laporan (SA, AG, MG) -->
        @can('reports.view')
            <div class="pt-2">
                <div class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Laporan</div>
                <div class="mt-1 space-y-1">
                    <a href="{{ route('reports.show', 'stock') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('reports.show') && request()->route('type') === 'stock' ? 'bg-slate-800 text-white font-medium' : '' }}">
                        <span class="w-2 h-2 mr-3 bg-teal-500 rounded-full"></span>
                        <span>Persediaan</span>
                    </a>

                    <a href="{{ route('reports.show', 'stock-ins') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('reports.show') && request()->route('type') === 'stock-ins' ? 'bg-slate-800 text-white font-medium' : '' }}">
                        <span class="w-2 h-2 mr-3 bg-cyan-500 rounded-full"></span>
                        <span>Barang Masuk</span>
                    </a>

                    <a href="{{ route('reports.show', 'stock-outs') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('reports.show') && request()->route('type') === 'stock-outs' ? 'bg-slate-800 text-white font-medium' : '' }}">
                        <span class="w-2 h-2 mr-3 bg-rose-500 rounded-full"></span>
                        <span>Barang Keluar</span>
                    </a>

                    <a href="{{ route('reports.show', 'adjustments') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('reports.show') && request()->route('type') === 'adjustments' ? 'bg-slate-800 text-white font-medium' : '' }}">
                        <span class="w-2 h-2 mr-3 bg-purple-500 rounded-full"></span>
                        <span>Penyesuaian</span>
                    </a>

                    <a href="{{ route('reports.show', 'movements') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('reports.show') && request()->route('type') === 'movements' ? 'bg-slate-800 text-white font-medium' : '' }}">
                        <span class="w-2 h-2 mr-3 bg-indigo-500 rounded-full"></span>
                        <span>Pergerakan Stok</span>
                    </a>
                </div>
            </div>
        @endcan

        <!-- Pengguna & Peran (Khusus SA) -->
        @canany(['users.manage', 'roles.view'])
            <div class="pt-2">
                <div class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Pengguna</div>
                <div class="mt-1 space-y-1">
                    @can('users.manage')
                        <a href="{{ route('users.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('users.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-blue-500 rounded-full"></span>
                            <span>Data Pengguna</span>
                        </a>
                    @endcan

                    @can('roles.view')
                        <a href="{{ route('roles.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('roles.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-purple-500 rounded-full"></span>
                            <span>Peran & Hak Akses</span>
                        </a>
                    @endcan
                </div>
            </div>
        @endcanany

        <!-- Sistem & Pengaturan -->
        @canany(['activity-logs.view', 'settings.manage'])
            <div class="pt-2">
                <div class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Sistem</div>
                <div class="mt-1 space-y-1">
                    @can('activity-logs.view')
                        <a href="{{ route('activity-logs.index') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('activity-logs.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-amber-500 rounded-full"></span>
                            <span>Log Aktivitas</span>
                        </a>
                    @endcan

                    @can('settings.manage')
                        <a href="{{ route('settings.edit') }}" class="flex items-center px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition {{ request()->routeIs('settings.*') ? 'bg-slate-800 text-white font-medium' : '' }}">
                            <span class="w-2 h-2 mr-3 bg-blue-500 rounded-full"></span>
                            <span>Pengaturan</span>
                        </a>
                    @endcan
                </div>
            </div>
        @endcanany
    </nav>
</aside>
