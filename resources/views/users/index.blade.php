<x-app-layout>
    <x-slot name="header">
        Manajemen Pengguna
    </x-slot>

    <div class="space-y-6" x-data="{ resetModalOpen: false, resetUserId: null, resetUserName: '' }">
        <!-- Top Toolbar & Filter -->
        <div class="bg-white p-4 sm:p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('users.index') }}" class="flex-1 flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="w-full sm:w-64">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari nama atau email..."
                        class="w-full text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                </div>

                <!-- Role Filter -->
                <div>
                    <select name="role" class="text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Peran</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="admin_gudang" {{ request('role') === 'admin_gudang' ? 'selected' : '' }}>Admin Gudang</option>
                        <option value="staff_gudang" {{ request('role') === 'staff_gudang' ? 'selected' : '' }}>Staff Gudang</option>
                        <option value="manajer" {{ request('role') === 'manajer' ? 'selected' : '' }}>Manajer</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" class="text-sm rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium rounded-lg transition">
                    Filter
                </button>

                @if(request()->hasAny(['q', 'role', 'status']))
                    <a href="{{ route('users.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">
                        Reset Filter
                    </a>
                @endif
            </form>

            <div>
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Pengguna
                </a>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th class="px-6 py-3.5">Nama & Email</th>
                            <th class="px-6 py-3.5">Peran</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5">Terakhir Masuk</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($users as $user)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                    @if($user->must_change_password)
                                        <span class="inline-block mt-1 text-[10px] font-semibold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded">Wajib Ganti Password</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $roleName = $user->roles->first()?->name ?? '-';
                                        $badgeColor = match($roleName) {
                                            'super_admin' => 'bg-purple-100 text-purple-800',
                                            'admin_gudang' => 'bg-blue-100 text-blue-800',
                                            'staff_gudang' => 'bg-emerald-100 text-emerald-800',
                                            'manajer' => 'bg-amber-100 text-amber-800',
                                            default => 'bg-slate-100 text-slate-800',
                                        };
                                        $roleLabel = match($roleName) {
                                            'super_admin' => 'Super Admin',
                                            'admin_gudang' => 'Admin Gudang',
                                            'staff_gudang' => 'Staff Gudang',
                                            'manajer' => 'Manajer',
                                            default => $roleName,
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeColor }}">
                                        {{ $roleLabel }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    @if($user->last_login_at)
                                        {{ $user->last_login_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB
                                        <div class="text-[11px] text-slate-400">IP: {{ $user->last_login_ip ?? '-' }}</div>
                                    @else
                                        <span class="text-slate-400">Belum pernah masuk</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-md transition">
                                        Ubah
                                    </a>

                                    @if(Auth::id() !== $user->id)
                                        <!-- Status Toggle Form -->
                                        <form method="POST" action="{{ route('users.status', $user) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin {{ $user->is_active ? 'menonaktifkan' : 'mengaktifkan' }} akun ini?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium rounded-md transition {{ $user->is_active ? 'text-amber-700 bg-amber-50 hover:bg-amber-100' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <!-- Reset Password Button -->
                                        <button
                                            type="button"
                                            @click="resetModalOpen = true; resetUserId = {{ $user->id }}; resetUserName = '{{ addslashes($user->name) }}'"
                                            class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-md transition"
                                        >
                                            Reset Password
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                    Tidak ada data pengguna yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="p-4 border-t border-slate-200 bg-slate-50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        <!-- Reset Password Modal -->
        <div
            x-show="resetModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
            x-transition
        >
            <div class="min-h-screen px-4 text-center flex items-center justify-center">
                <div class="fixed inset-0 bg-slate-900/60 transition-opacity" @click="resetModalOpen = false"></div>

                <div class="inline-block w-full max-w-md p-6 my-8 text-left bg-white rounded-2xl shadow-xl transform transition-all z-10">
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Reset Kata Sandi Pengguna</h3>
                    <p class="text-xs text-slate-600 mb-4">
                        Tetapkan kata sandi sementara untuk pengguna <span class="font-bold text-slate-800" x-text="resetUserName"></span>. Pengguna akan diwajibkan mengganti kata sandi pada login berikutnya dan semua sesi aktifnya akan diputus.
                    </p>

                    <form :action="'/users/' + resetUserId + '/reset-password'" method="POST">
                        @csrf
                        <div class="mb-4">
                            <x-input-label for="reset_password" value="Kata Sandi Sementara Baru" />
                            <x-text-input id="reset_password" name="password" type="password" class="mt-1 block w-full text-sm" required placeholder="Minimal 10 karakter (huruf besar, kecil, angka)" />
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="resetModalOpen = false" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm">
                                Tetapkan Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
