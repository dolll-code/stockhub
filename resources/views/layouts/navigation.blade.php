<header class="bg-white border-b border-slate-200 sticky top-0 z-30">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Left: Mobile Toggle & Title -->
            <div class="flex items-center space-x-3">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="p-2 rounded-md text-slate-500 hover:text-slate-700 hover:bg-slate-100 lg:hidden focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Toggle Sidebar"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h1 class="text-lg font-semibold text-slate-800">
                    {{ $header ?? 'Dashboard' }}
                </h1>
            </div>

            <!-- Right: Notifications & User Menu -->
            <div class="flex items-center space-x-4">
                <!-- Low Stock Notification Bell (PRD §5.11 FR-NTF-01) -->
                <button
                    type="button"
                    class="relative p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                    title="Notifikasi Stok Minimum"
                >
                    <span class="sr-only">Notifikasi Stok</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <!-- Notification Badge Counter -->
                    <span class="absolute top-1.5 right-1.5 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-amber-500 rounded-full">
                        0
                    </span>
                </button>

                <!-- User Dropdown -->
                <div class="relative" x-data="{ userMenuOpen: false }">
                    <button
                        @click="userMenuOpen = !userMenuOpen"
                        class="flex items-center space-x-2 p-1.5 rounded-lg text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                    >
                        <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                            {{ substr(Auth::user()?->name ?? 'U', 0, 1) }}
                        </div>
                        <span class="hidden md:inline-block text-sm font-medium text-slate-700">
                            {{ Auth::user()?->name ?? 'Pengguna' }}
                        </span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        x-show="userMenuOpen"
                        @click.away="userMenuOpen = false"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 border border-slate-200 z-50"
                        style="display: none;"
                    >
                        <div class="px-4 py-2 text-xs text-slate-400 border-b border-slate-100">
                            Masuk sebagai<br>
                            <span class="font-medium text-slate-800 truncate block">{{ Auth::user()?->email }}</span>
                        </div>

                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition">
                            Profil Saya
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                                Keluar (Logout)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
