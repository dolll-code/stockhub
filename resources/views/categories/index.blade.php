<x-app-layout>
    <x-slot name="header">
        Kategori Barang
    </x-slot>

    <div class="space-y-6" x-data="{ statusModalOpen: false, statusFormAction: '', statusCategoryName: '', isCurrentlyActive: false }">
        <!-- Toolbar & Filter -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('categories.index') }}" class="flex-1 flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="w-full sm:w-64">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari nama kategori..."
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" class="text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <!-- Per Page -->
                <div>
                    <select name="per_page" class="text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15 per baris</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 per baris</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 per baris</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 per baris</option>
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium rounded-lg transition">
                    Filter
                </button>

                @if(request()->hasAny(['q', 'status', 'per_page']))
                    <a href="{{ route('categories.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                        Reset Filter
                    </a>
                @endif
            </form>

            @can('categories.manage')
                <div>
                    <a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Tambah Kategori
                    </a>
                </div>
            @endcan
        </div>

        <!-- Categories Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th class="px-6 py-3.5">Nama Kategori</th>
                            <th class="px-6 py-3.5">Deskripsi</th>
                            <th class="px-6 py-3.5 text-center">Jumlah Barang</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($categories as $category)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-medium text-slate-900">
                                    {{ $category->name }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 max-w-md truncate">
                                    {{ $category->description ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                        {{ number_format($category->products_count, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($category->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    @can('categories.manage')
                                        <a href="{{ route('categories.edit', $category) }}" class="text-blue-600 hover:text-blue-900 font-medium text-xs">
                                            Ubah
                                        </a>

                                        <button
                                            type="button"
                                            @click="
                                                statusModalOpen = true;
                                                statusFormAction = '{{ route('categories.status', $category) }}';
                                                statusCategoryName = '{{ addslashes($category->name) }}';
                                                isCurrentlyActive = {{ $category->is_active ? 'true' : 'false' }};
                                            "
                                            class="text-xs font-medium {{ $category->is_active ? 'text-amber-600 hover:text-amber-900' : 'text-emerald-600 hover:text-emerald-900' }}"
                                        >
                                            {{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400">Hanya Lihat</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    Tidak ada data kategori yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($categories->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>

        <!-- Status Confirmation Modal -->
        <div
            x-show="statusModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            x-cloak
        >
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="statusModalOpen = false"></div>

                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 relative z-10 border border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-800" x-text="isCurrentlyActive ? 'Konfirmasi Penonaktifan Kategori' : 'Konfirmasi Pengaktifan Kategori'"></h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Apakah Anda yakin ingin <span class="font-medium" x-text="isCurrentlyActive ? 'menonaktifkan' : 'mengaktifkan'"></span> kategori <span class="font-semibold text-slate-800" x-text="statusCategoryName"></span>?
                    </p>
                    <p class="mt-1 text-xs text-slate-500" x-show="isCurrentlyActive">
                        Perhatian: Kategori tidak dapat dinonaktifkan jika masih digunakan oleh barang aktif.
                    </p>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="statusModalOpen = false"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 transition"
                        >
                            Batal
                        </button>
                        <form :action="statusFormAction" method="POST">
                            @csrf
                            @method('PATCH')
                            <button
                                type="submit"
                                class="px-4 py-2 text-white text-sm font-medium rounded-lg transition"
                                :class="isCurrentlyActive ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700'"
                                x-text="isCurrentlyActive ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan'"
                            >
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
