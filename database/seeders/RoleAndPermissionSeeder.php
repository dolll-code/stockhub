<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define all 26 permissions per PRD §4.2
        $permissions = [
            'dashboard.view',
            'users.manage',
            'roles.view',
            'settings.manage',
            'categories.view',
            'categories.manage',
            'units.view',
            'units.manage',
            'suppliers.view',
            'suppliers.manage',
            'products.view',
            'products.view-price',
            'products.manage',
            'stocks.view',
            'stock-ins.view',
            'stock-ins.create',
            'stock-ins.cancel-confirmed',
            'stock-outs.view',
            'stock-outs.create',
            'stock-outs.cancel-confirmed',
            'adjustments.view',
            'adjustments.create',
            'stock-movements.view',
            'reports.view',
            'reports.export',
            'activity-logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. Define 4 fixed roles and their permissions per PRD §4.2
        $rolesWithPermissions = [
            'super_admin' => $permissions,
            'admin_gudang' => [
                'dashboard.view',
                'categories.view',
                'categories.manage',
                'units.view',
                'units.manage',
                'suppliers.view',
                'suppliers.manage',
                'products.view',
                'products.view-price',
                'products.manage',
                'stocks.view',
                'stock-ins.view',
                'stock-ins.create',
                'stock-ins.cancel-confirmed',
                'stock-outs.view',
                'stock-outs.create',
                'stock-outs.cancel-confirmed',
                'adjustments.view',
                'adjustments.create',
                'stock-movements.view',
                'reports.view',
                'reports.export',
            ],
            'staff_gudang' => [
                'dashboard.view',
                'categories.view',
                'units.view',
                'suppliers.view',
                'products.view',
                'stocks.view',
                'stock-ins.view',
                'stock-ins.create',
                'stock-outs.view',
                'stock-outs.create',
            ],
            'manajer' => [
                'dashboard.view',
                'categories.view',
                'units.view',
                'suppliers.view',
                'products.view',
                'products.view-price',
                'stocks.view',
                'stock-ins.view',
                'stock-outs.view',
                'adjustments.view',
                'stock-movements.view',
                'reports.view',
                'reports.export',
            ],
        ];

        foreach ($rolesWithPermissions as $roleName => $assignedPermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($assignedPermissions);
        }
    }
}
