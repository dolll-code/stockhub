<?php

use App\Enums\MovementType;
use App\Enums\StockInSource;
use App\Enums\StockOutPurpose;
use App\Enums\TransactionStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\StockOut;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Reports\StockInReportQuery;
use App\Reports\StockMovementReportQuery;
use App\Reports\StockOutReportQuery;
use App\Reports\StockReportQuery;
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

    $this->category = Category::create(['name' => 'Elektronik', 'is_active' => true]);
    $this->unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);
    $this->supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'PT Mitra Utama', 'is_active' => true]);

    $this->product = Product::factory()->create([
        'code' => 'BRG-0001',
        'name' => 'Mouse Gaming',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'main_supplier_id' => $this->supplier->id,
        'is_active' => true,
    ]);
});

test('TC-RPT-01: Stock In report includes ONLY confirmed transactions (AC-RPT-01, BR-21)', function () {
    // 1. Confirmed stock in
    $confirmed = StockIn::create([
        'number' => 'IN-202611-0001',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::PURCHASE->value,
        'supplier_id' => $this->supplier->id,
        'status' => TransactionStatus::CONFIRMED->value,
        'created_by' => $this->adminGudang->id,
    ]);
    $confirmed->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_price' => 50000,
    ]);

    // 2. Draft stock in
    $draft = StockIn::create([
        'number' => 'IN-202611-0002',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::PURCHASE->value,
        'supplier_id' => $this->supplier->id,
        'status' => TransactionStatus::DRAFT->value,
        'created_by' => $this->adminGudang->id,
    ]);
    $draft->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 15,
        'unit_price' => 50000,
    ]);

    // 3. Cancelled stock in
    $cancelled = StockIn::create([
        'number' => 'IN-202611-0003',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::PURCHASE->value,
        'supplier_id' => $this->supplier->id,
        'status' => TransactionStatus::CANCELLED->value,
        'created_by' => $this->adminGudang->id,
    ]);
    $cancelled->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 20,
        'unit_price' => 50000,
    ]);

    $response = $this->actingAs($this->adminGudang)->get('/reports/stock-ins');
    $response->assertOk();
    $response->assertSee('IN-202611-0001');
    $response->assertDontSee('IN-202611-0002');
    $response->assertDontSee('IN-202611-0003');

    // Query Object direct check
    $query = new StockInReportQuery;
    expect($query->count())->toBe(1);
    expect($query->get()->first()->stockIn->number)->toBe('IN-202611-0001');
});

test('TC-RPT-02: Stock Movement report includes BOTH original and reversal movements (AC-RPT-02, FR-RPT-05)', function () {
    // 1. Original movement: stock_in +20
    StockMovement::create([
        'product_id' => $this->product->id,
        'movement_type' => MovementType::STOCK_IN,
        'reference_number' => 'IN-202611-0010',
        'movement_date' => now()->toDateString(),
        'qty_before' => 0,
        'qty_change' => 20,
        'qty_after' => 20,
        'user_id' => $this->adminGudang->id,
    ]);

    // 2. Reversal movement: stock_in_cancel -20
    StockMovement::create([
        'product_id' => $this->product->id,
        'movement_type' => MovementType::STOCK_IN_CANCEL,
        'reference_number' => 'IN-202611-0010',
        'movement_date' => now()->toDateString(),
        'qty_before' => 20,
        'qty_change' => -20,
        'qty_after' => 0,
        'user_id' => $this->adminGudang->id,
    ]);

    $response = $this->actingAs($this->adminGudang)->get('/reports/movements');
    $response->assertOk();
    $response->assertSee('Barang Masuk');
    $response->assertSee('Pembatalan Barang Masuk');
    $response->assertSee('IN-202611-0010');

    $query = new StockMovementReportQuery;
    expect($query->count())->toBe(2);
});

test('TC-RPT-03: Query Objects return consistent row count and content across paginate() and get() (AC-RPT-03, BR-31)', function () {
    // Test StockOutReportQuery
    $out = StockOut::create([
        'number' => 'OUT-202611-0001',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Lab Komputer',
        'status' => TransactionStatus::CONFIRMED->value,
        'created_by' => $this->adminGudang->id,
    ]);
    $out->details()->createMany([
        ['product_id' => $this->product->id, 'quantity' => 2],
    ]);

    $query = new StockOutReportQuery;
    $paginated = $query->paginate(15);
    $all = $query->get();

    expect($paginated->total())->toBe(1);
    expect($all->count())->toBe(1);
    expect($all->first()->stockOut->destination)->toBe('Lab Komputer');
});

test('TC-RPT-04: Stock report with as_of_date calculates stock from movements up to that date (AC-RPT-04, FR-RPT-01)', function () {
    // Product has current_stock 30 today
    $this->product->forceFill(['current_stock' => 30.0])->save();

    // Yesterday: movement +10
    StockMovement::create([
        'product_id' => $this->product->id,
        'movement_type' => MovementType::OPENING_BALANCE,
        'movement_date' => now()->subDay()->toDateString(),
        'qty_before' => 0,
        'qty_change' => 10,
        'qty_after' => 10,
        'user_id' => $this->adminGudang->id,
    ]);

    // Today: movement +20
    StockMovement::create([
        'product_id' => $this->product->id,
        'movement_type' => MovementType::STOCK_IN,
        'movement_date' => now()->toDateString(),
        'qty_before' => 10,
        'qty_change' => 20,
        'qty_after' => 30,
        'user_id' => $this->adminGudang->id,
    ]);

    // 1. As-of yesterday: stock should be 10 (not 30!)
    $yesterdayStr = now()->subDay()->toDateString();
    $queryYesterday = new StockReportQuery(['as_of_date' => $yesterdayStr]);
    $resultsYesterday = $queryYesterday->get();
    $targetProdYesterday = $resultsYesterday->firstWhere('id', $this->product->id);

    expect((float) $targetProdYesterday->as_of_stock)->toBe(10.0);

    // 2. HTTP Request with as_of_date yesterday
    $response = $this->actingAs($this->adminGudang)->get("/reports/stock?as_of_date={$yesterdayStr}");
    $response->assertOk();
    $response->assertSee('10');
});

test('Staff Gudang (SG) is denied access (403) to all reports, Manajer has read-only access (AC-RPT-06, §4)', function () {
    // 1. Staff Gudang is forbidden across all report tabs
    $this->actingAs($this->staffGudang)->get('/reports/stock')->assertForbidden();
    $this->actingAs($this->staffGudang)->get('/reports/stock-ins')->assertForbidden();
    $this->actingAs($this->staffGudang)->get('/reports/stock-outs')->assertForbidden();
    $this->actingAs($this->staffGudang)->get('/reports/adjustments')->assertForbidden();
    $this->actingAs($this->staffGudang)->get('/reports/movements')->assertForbidden();

    // 2. Manajer can view all reports (read-only)
    $this->actingAs($this->manajer)->get('/reports/stock')->assertOk();
    $this->actingAs($this->manajer)->get('/reports/stock-ins')->assertOk();
    $this->actingAs($this->manajer)->get('/reports/stock-outs')->assertOk();
    $this->actingAs($this->manajer)->get('/reports/adjustments')->assertOk();
    $this->actingAs($this->manajer)->get('/reports/movements')->assertOk();
});
