<x-app-layout>
    <x-slot name="header">
        Matriks Peran dan Hak Akses
    </x-slot>

    <div class="space-y-6">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 sm:p-5">
            <h3 class="text-sm font-semibold text-blue-900">Ketentuan Otorisasi Sistem (PRD §4.2)</h3>
            <p class="mt-1 text-xs text-blue-700">
                Aplikasi StockHub menetapkan 4 peran tetap (*fixed roles*) dengan satu peran untuk setiap pengguna. Hak akses ditegakkan secara ketat pada lapisan backend (Policy & Middleware). Matriks di bawah bersifat *read-only* (FR-USR-06).
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-slate-700 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Kapabilitas / Modul</th>
                            <th class="px-6 py-4">Permission Key</th>
                            <th class="px-4 py-4 text-center">Super Admin (SA)</th>
                            <th class="px-4 py-4 text-center">Admin Gudang (AG)</th>
                            <th class="px-4 py-4 text-center">Staff Gudang (SG)</th>
                            <th class="px-4 py-4 text-center">Manajer (MG)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @php
                            $roleSA = $roles->firstWhere('name', 'super_admin');
                            $roleAG = $roles->firstWhere('name', 'admin_gudang');
                            $roleSG = $roles->firstWhere('name', 'staff_gudang');
                            $roleMG = $roles->firstWhere('name', 'manajer');

                            $ownPermissions = ['stock-ins.view', 'stock-ins.create', 'stock-outs.view', 'stock-outs.create'];
                        @endphp

                        @foreach($permissions as $permission)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-3 font-medium text-slate-900">
                                    {{ ucwords(str_replace(['.', '-'], ' ', $permission->name)) }}
                                </td>
                                <td class="px-6 py-3 font-mono text-slate-500">
                                    {{ $permission->name }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($roleSA && $roleSA->hasPermissionTo($permission->name))
                                        <span class="text-emerald-600 font-bold text-base">✔</span>
                                    @else
                                        <span class="text-slate-300 font-bold">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($roleAG && $roleAG->hasPermissionTo($permission->name))
                                        <span class="text-emerald-600 font-bold text-base">✔</span>
                                    @else
                                        <span class="text-slate-300 font-bold">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($roleSG && $roleSG->hasPermissionTo($permission->name))
                                        @if(in_array($permission->name, $ownPermissions, true))
                                            <span class="text-blue-600 font-semibold text-xs">✔ (own)</span>
                                        @else
                                            <span class="text-emerald-600 font-bold text-base">✔</span>
                                        @endif
                                    @else
                                        <span class="text-slate-300 font-bold">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($roleMG && $roleMG->hasPermissionTo($permission->name))
                                        <span class="text-emerald-600 font-bold text-base">✔</span>
                                    @else
                                        <span class="text-slate-300 font-bold">–</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
