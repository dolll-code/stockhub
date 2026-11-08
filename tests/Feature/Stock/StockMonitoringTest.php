<?php

use App\Enums\StockStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SettingSeeder::class);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->adminGudang = User::factory()->create();
    $this->adminGudang->assignRole('admin_gudang');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('staff_gudang');

    $this->manajer = User::factory()->create();
    $this->manajer->assignRole('manajer');

    $this->category = Category::create(['name' => 'Alat Tulis', 'is_active' => true]);
    $this->unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);
});

test('TC-STK-01: stock status boundaries with min_stock = 5 (0 -> habis, 3 -> minimum, 5 -> minimum, 6 -> aman) (AC-STK-01, BR-18)', function () {
    expect(StockStatus::fromStock(0, 5))->toBe(StockStatus::HABIS);
    expect(StockStatus::fromStock(3, 5))->toBe(StockStatus::MINIMUM);
    expect(StockStatus::fromStock(5, 5))->toBe(StockStatus::MINIMUM);
    expect(StockStatus::fromStock(6, 5))->toBe(StockStatus::AMAN);
});

test('TC-STK-02: stock status boundaries with min_stock = 0 (0 -> habis, >0 -> aman) (AC-STK-02, BR-18)', function () {
    expect(StockStatus::fromStock(0, 0))->toBe(StockStatus::HABIS);
    expect(StockStatus::fromStock(1, 0))->toBe(StockStatus::AMAN);
    expect(StockStatus::fromStock(100, 0))->toBe(StockStatus::AMAN);
});

test('TC-STK-04: live bell notification displays exact count of active habis + minimum products, excluding inactive (AC-STK-04, FR-NTF-01)', function () {
    // 2 active habis (stock = 0)
    Product::factory()->count(2)->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 0,
        'min_stock' => 5,
        'is_active' => true,
    ]);

    // 3 active minimum (stock <= min_stock, > 0)
    Product::factory()->count(3)->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 3,
        'min_stock' => 5,
        'is_active' => true,
    ]);

    // 1 inactive habis (should NOT be counted in bell, BR-04)
    Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 0,
        'min_stock' => 5,
        'is_active' => false,
    ]);

    // Total active habis + minimum = 2 + 3 = 5
    $response = $this->actingAs($this->staffGudang)->get('/dashboard');
    $response->assertOk();

    // Verify navbar bell contains '5'
    $response->assertSee('title="Notifikasi Stok Minimum (5 barang kritis)"', false);
});

test('TC-STK-05: changing min_stock directly alters stock_status without creating any movement (AC-STK-06, BR-29)', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 8,
        'min_stock' => 5, // aman (8 > 5)
        'is_active' => true,
    ]);

    expect($product->stock_status)->toBe(StockStatus::AMAN);

    // Update min_stock to 10 -> status becomes minimum (8 <= 10)
    $this->actingAs($this->adminGudang)->put("/products/{$product->id}", [
        'name' => $product->name,
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'min_stock' => 10,
    ]);

    $fresh = $product->fresh();
    expect((float) $fresh->min_stock)->toBe(10.0);
    expect($fresh->stock_status)->toBe(StockStatus::MINIMUM);

    // Zero movements created
    expect(StockMovement::where('product_id', $product->id)->count())->toBe(0);
});

test('stocks low page only lists active products with habis or minimum status, ordered by criticality (FR-STK-02)', function () {
    // 1. Habis active (criticality rank 0)
    $habis = Product::factory()->create([
        'name' => 'Barang Habis',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 0,
        'min_stock' => 10,
        'is_active' => true,
    ]);

    // 2. Low active with ratio 2/10 = 0.2 (criticality rank 1)
    $low1 = Product::factory()->create([
        'name' => 'Barang Low Parah',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 2,
        'min_stock' => 10,
        'is_active' => true,
    ]);

    // 3. Low active with ratio 8/10 = 0.8 (criticality rank 2)
    $low2 = Product::factory()->create([
        'name' => 'Barang Low Mepet',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 8,
        'min_stock' => 10,
        'is_active' => true,
    ]);

    // 4. Safe active (should NOT be on /stocks/low)
    $safe = Product::factory()->create([
        'name' => 'Barang Aman',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 20,
        'min_stock' => 10,
        'is_active' => true,
    ]);

    // 5. Inactive habis (should NOT be on /stocks/low)
    $inactive = Product::factory()->create([
        'name' => 'Barang Inactive',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 0,
        'min_stock' => 10,
        'is_active' => false,
    ]);

    $response = $this->actingAs($this->adminGudang)->get('/stocks/low');
    $response->assertOk();
    $response->assertSee('Barang Habis');
    $response->assertSee('Barang Low Parah');
    $response->assertSee('Barang Low Mepet');
    $response->assertDontSee('Barang Aman');
    $response->assertDontSee('Barang Inactive');

    // Verify ordering: Habis appears before Low Parah, and Low Parah before Low Mepet
    $content = $response->getContent();
    $posHabis = strpos($content, 'Barang Habis');
    $posLow1 = strpos($content, 'Barang Low Parah');
    $posLow2 = strpos($content, 'Barang Low Mepet');

    expect($posHabis)->toBeLessThan($posLow1);
    expect($posLow1)->toBeLessThan($posLow2);
});

test('TC-STK-05 / AC-STK-05: Staff Gudang accessing /stock-movements returns HTTP 403, SA/AG/MG returns 200', function () {
    // 1. Staff Gudang is forbidden
    $this->actingAs($this->staffGudang)->get('/stock-movements')->assertForbidden();

    // 2. Admin Gudang is allowed
    $this->actingAs($this->adminGudang)->get('/stock-movements')->assertOk();

    // 3. Manajer is allowed (read-only)
    $this->actingAs($this->manajer)->get('/stock-movements')->assertOk();

    // 4. Super Admin is allowed
    $this->actingAs($this->superAdmin)->get('/stock-movements')->assertOk();
});
