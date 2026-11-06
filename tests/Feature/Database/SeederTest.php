<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\InitialSuperAdminSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('production seeders run successfully and are idempotent', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->seed(SettingSeeder::class);
    $this->seed(InitialSuperAdminSeeder::class);

    // Re-run to test idempotency
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->seed(SettingSeeder::class);
    $this->seed(InitialSuperAdminSeeder::class);

    expect(Role::count())->toBe(4);
    expect(Unit::count())->toBe(9);
    expect(Setting::count())->toBe(5);

    $superAdminRole = Role::findByName('super_admin', 'web');
    expect($superAdminRole->permissions()->count())->toBe(26);

    $adminUser = User::where('email', env('INITIAL_ADMIN_EMAIL', 'admin@example.com'))->first();
    expect($adminUser)->not->toBeNull();
    expect($adminUser->hasRole('super_admin'))->toBeTrue();
    expect($adminUser->must_change_password)->toBeTrue();
});

test('demo seeder creates sample categories suppliers and products with zero stock balance', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(UnitSeeder::class);
    $this->seed(SettingSeeder::class);
    $this->seed(InitialSuperAdminSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Category::count())->toBeGreaterThanOrEqual(6);
    expect(Supplier::count())->toBeGreaterThanOrEqual(2);
    expect(Product::count())->toBeGreaterThanOrEqual(2);

    // Check all demo products have current_stock = 0 (BR-06, Instruction 7)
    $products = Product::all();
    foreach ($products as $p) {
        expect((float) $p->current_stock)->toBe(0.0);
    }

    // Check demo users exist and have correct single role
    $ag = User::where('email', 'ag@example.com')->first();
    expect($ag)->not->toBeNull();
    expect($ag->hasRole('admin_gudang'))->toBeTrue();

    $sg = User::where('email', 'sg@example.com')->first();
    expect($sg)->not->toBeNull();
    expect($sg->hasRole('staff_gudang'))->toBeTrue();

    $mg = User::where('email', 'mg@example.com')->first();
    expect($mg)->not->toBeNull();
    expect($mg->hasRole('manajer'))->toBeTrue();
});

test('demo seeder throws exception in production environment', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(function () {
        $seeder = new DemoSeeder;
        $seeder->run();
    })->toThrow(RuntimeException::class, 'DemoSeeder dilarang keras dijalankan pada lingkungan produksi!');
});
