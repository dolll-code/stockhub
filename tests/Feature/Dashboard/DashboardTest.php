<?php

use App\Enums\StockInSource;
use App\Enums\TransactionStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Supplier;
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

    $this->staffGudang1 = User::factory()->create();
    $this->staffGudang1->assignRole('staff_gudang');

    $this->staffGudang2 = User::factory()->create();
    $this->staffGudang2->assignRole('staff_gudang');

    $this->manajer = User::factory()->create();
    $this->manajer->assignRole('manajer');

    $this->category = Category::create(['name' => 'Elektronik', 'is_active' => true]);
    $this->unitPcs = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);
    $this->unitKg = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'allow_decimal' => true, 'is_active' => true]);
    $this->supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'PT Suplai Utama', 'is_active' => true]);
});

test('TC-DSH-01: dashboard monthly confirmed stock-in count counts only confirmed transactions this month (AC-DSH-01, FR-DSH-01)', function () {
    // 3 confirmed, 2 draft, 1 cancelled this month
    StockIn::factory()->count(3)->create([
        'transaction_date' => now()->toDateString(),
        'status' => TransactionStatus::CONFIRMED->value,
    ]);
    StockIn::factory()->count(2)->create([
        'transaction_date' => now()->toDateString(),
        'status' => TransactionStatus::DRAFT->value,
    ]);
    StockIn::factory()->count(1)->create([
        'transaction_date' => now()->toDateString(),
        'status' => TransactionStatus::CANCELLED->value,
    ]);

    // 1 confirmed from last month (should NOT be counted)
    StockIn::factory()->create([
        'transaction_date' => now()->subMonth()->toDateString(),
        'status' => TransactionStatus::CONFIRMED->value,
    ]);

    $response = $this->actingAs($this->adminGudang)->get('/dashboard');
    $response->assertOk();
    $response->assertViewHas('monthlyStockInsCount', 3);
});

test('TC-DSH-02: dashboard stock status counts: A stok 0, B stok <= min (>0), C stok > min -> habis=1, min=1, tersedia=2 (AC-DSH-02)', function () {
    // Product A: stock 0, min 5 -> habis
    Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'current_stock' => 0,
        'min_stock' => 5,
        'is_active' => true,
    ]);

    // Product B: stock 4, min 5 -> minimum (tersedia & minimum)
    Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'current_stock' => 4,
        'min_stock' => 5,
        'is_active' => true,
    ]);

    // Product C: stock 10, min 5 -> aman (tersedia)
    Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'current_stock' => 10,
        'min_stock' => 5,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->superAdmin)->get('/dashboard');
    $response->assertOk();
    $response->assertViewHas('outOfStockProductsCount', 1);
    $response->assertViewHas('lowStockProductsCount', 1);
    $response->assertViewHas('availableStockProductsCount', 2);
});

test('TC-DSH-03: Staff Gudang dashboard only displays own transactions, limited cards, and no global chart (AC-DSH-03, FR-DSH-05)', function () {
    // Transaction by Staff 1
    $tx1 = StockIn::create([
        'number' => 'IN-202611-0001',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::DRAFT->value,
        'created_by' => $this->staffGudang1->id,
    ]);

    // Transaction by Staff 2
    $tx2 = StockIn::create([
        'number' => 'IN-202611-0002',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::DRAFT->value,
        'created_by' => $this->staffGudang2->id,
    ]);

    $responseSG1 = $this->actingAs($this->staffGudang1)->get('/dashboard');
    $responseSG1->assertOk();
    $responseSG1->assertViewHas('isStaff', true);
    $responseSG1->assertSee($tx1->number);
    $responseSG1->assertDontSee($tx2->number);

    // SG does NOT see global supplier count or chart component
    $responseSG1->assertDontSee('Perbandingan Transaksi Masuk vs Keluar');
    $responseSG1->assertDontSee('Total Barang Aktif');
});

test('TC-DSH-04: dashboard does NOT aggregate physical quantities across different units (AC-DSH-04, BR-27)', function () {
    Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'current_stock' => 100, // pcs
        'is_active' => true,
    ]);
    Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitKg->id,
        'current_stock' => 50, // kg
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->superAdmin)->get('/dashboard');
    $response->assertOk();

    // Verifies that 150 (sum of pcs + kg) does not appear as a total quantity anywhere on the dashboard
    $content = $response->getContent();
    expect($content)->not->toContain('150.000');
    expect($content)->not->toContain('Total Kuantitas');
});

test('dashboard chart endpoint returns data for SA/AG/MG but returns 403 for SG (FR-DSH-02, PRD §12.3)', function () {
    StockIn::factory()->create([
        'transaction_date' => now()->toDateString(),
        'status' => TransactionStatus::CONFIRMED->value,
    ]);
    StockOut::factory()->create([
        'transaction_date' => now()->toDateString(),
        'status' => TransactionStatus::CONFIRMED->value,
    ]);

    // 1. Super Admin access chart
    $responseSA = $this->actingAs($this->superAdmin)->getJson('/ajax/dashboard/chart?period=daily');
    $responseSA->assertOk();
    $responseSA->assertJsonStructure(['labels', 'stock_in', 'stock_out']);

    // 2. Manajer access chart
    $responseMG = $this->actingAs($this->manajer)->getJson('/ajax/dashboard/chart?period=monthly');
    $responseMG->assertOk();
    $responseMG->assertJsonStructure(['labels', 'stock_in', 'stock_out']);

    // 3. Staff Gudang access chart -> 403
    $this->actingAs($this->staffGudang1)->getJson('/ajax/dashboard/chart?period=daily')
        ->assertForbidden();
});
