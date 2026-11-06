<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Mandatory idempotent production seeders
        $this->call([
            RoleAndPermissionSeeder::class,
            UnitSeeder::class,
            SettingSeeder::class,
            InitialSuperAdminSeeder::class,
        ]);

        // 2. Demo seeder strictly for local/testing environment
        if (! app()->isProduction()) {
            $this->call([
                DemoSeeder::class,
            ]);
        }
    }
}
