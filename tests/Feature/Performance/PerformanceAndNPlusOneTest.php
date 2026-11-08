<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SettingSeeder::class);

    $this->adminGudang = User::factory()->create();
    $this->adminGudang->assignRole('admin_gudang');

    $this->category = Category::create(['name' => 'Elektronik Massal', 'is_active' => true]);
    $this->unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);
    $this->supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'PT Suplai Massal', 'is_active' => true]);
});

test('performance & N+1 audit: preventLazyLoading does NOT trigger on dashboard, stocks, movements, and reports', function () {
    // Force preventLazyLoading on
    Model::preventLazyLoading(true);

    // Seed 30 products
    for ($i = 1; $i <= 30; $i++) {
        $product = Product::create([
            'code' => sprintf('BRG-%04d', $i + 1000),
            'name' => 'Produk Performa '.$i,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'main_supplier_id' => $this->supplier->id,
            'current_stock' => $i % 3 === 0 ? 0 : 15,
            'min_stock' => 5,
            'purchase_price' => 50000,
            'is_active' => true,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'movement_date' => now()->toDateString(),
            'qty_before' => 0,
            'qty_change' => 15,
            'qty_after' => 15,
            'user_id' => $this->adminGudang->id,
        ]);
    }

    // 1. Dashboard
    $this->actingAs($this->adminGudang)->get('/dashboard')->assertOk();

    // 2. Stocks index
    $this->actingAs($this->adminGudang)->get('/stocks')->assertOk();

    // 3. Stocks low
    $this->actingAs($this->adminGudang)->get('/stocks/low')->assertOk();

    // 4. Stock movements global
    $this->actingAs($this->adminGudang)->get('/stock-movements')->assertOk();

    // 5. Reports (all 5 types)
    $this->actingAs($this->adminGudang)->get('/reports/stock')->assertOk();
    $this->actingAs($this->adminGudang)->get('/reports/stock-ins')->assertOk();
    $this->actingAs($this->adminGudang)->get('/reports/stock-outs')->assertOk();
    $this->actingAs($this->adminGudang)->get('/reports/adjustments')->assertOk();
    $this->actingAs($this->adminGudang)->get('/reports/movements')->assertOk();
});

test('query count efficiency: /stocks does not execute runaway queries for 25 items', function () {
    // Seed 25 products
    for ($i = 1; $i <= 25; $i++) {
        Product::create([
            'code' => sprintf('BRG-%04d', $i + 2000),
            'name' => 'Produk Efisiensi '.$i,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'current_stock' => 10,
            'min_stock' => 5,
            'is_active' => true,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($this->adminGudang)->get('/stocks?per_page=25');
    $response->assertOk();

    $queries = DB::getQueryLog();
    // Query count should be constant (typically <= 12 queries: auth, user, permissions, categories list, products paginate, count, etc.)
    // If there were N+1, there would be > 35 queries!
    expect(count($queries))->toBeLessThan(15);
});
