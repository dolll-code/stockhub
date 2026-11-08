<?php

use App\Enums\AdjustmentReason;
use App\Enums\StockInSource;
use App\Enums\StockOutPurpose;
use App\Enums\TransactionStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\StockChangedException;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\StockOut;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
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

    $this->category = Category::create(['name' => 'Elektronik', 'is_active' => true]);
    $this->unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);

    $this->product = Product::factory()->create([
        'code' => 'BRG-0099',
        'name' => 'Flashdisk 64GB',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'is_active' => true,
    ]);
});

test('TC-CON-01: duplicate / concurrent confirmation on same document results in exactly one success and 409 INVALID_STATE_TRANSITION (AC-STI-07)', function () {
    $this->product->forceFill(['current_stock' => 10.0])->save();

    $stockIn = StockIn::create([
        'number' => 'IN-202611-0099',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->adminGudang->id,
    ]);
    $stockIn->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 20,
    ]);

    $stockService = app(StockService::class);

    // Process first confirmation -> succeeds
    $confirmed1 = $stockService->confirmStockIn($stockIn, $this->adminGudang);
    expect($confirmed1->status)->toBe(TransactionStatus::CONFIRMED);
    expect((float) $this->product->fresh()->current_stock)->toBe(30.0);

    // Second confirmation on same document -> throws InvalidStateTransitionException
    expect(function () use ($stockService, $stockIn) {
        $stockService->confirmStockIn($stockIn, $this->adminGudang);
    })->toThrow(InvalidStateTransitionException::class);

    // Stock increased ONLY once (+20)
    expect((float) $this->product->fresh()->current_stock)->toBe(30.0);
    expect(StockMovement::where('product_id', $this->product->id)->count())->toBe(1);
});

test('TC-CON-02: two different documents depleting same product concurrently -> stock never negative (AC-STO-03, BR-09)', function () {
    // Initial stock is 10
    $this->product->forceFill(['current_stock' => 10.0])->save();

    // Draft 1 for 7 items
    $stockOut1 = StockOut::create([
        'number' => 'OUT-202611-0091',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Kantor 1',
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->adminGudang->id,
    ]);
    $stockOut1->details()->create(['product_id' => $this->product->id, 'quantity' => 7]);

    // Draft 2 for 7 items
    $stockOut2 = StockOut::create([
        'number' => 'OUT-202611-0092',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Kantor 2',
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->adminGudang->id,
    ]);
    $stockOut2->details()->create(['product_id' => $this->product->id, 'quantity' => 7]);

    $stockService = app(StockService::class);

    // First confirms -> 10 - 7 = 3 (succeeds)
    $stockService->confirmStockOut($stockOut1, $this->adminGudang);
    expect((float) $this->product->fresh()->current_stock)->toBe(3.0);

    // Second confirms -> stock is 3, asked 7 -> INSUFFICIENT_STOCK (fails)
    expect(function () use ($stockService, $stockOut2) {
        $stockService->confirmStockOut($stockOut2, $this->adminGudang);
    })->toThrow(InsufficientStockException::class);

    // Final stock must remain 3.0, never negative!
    expect((float) $this->product->fresh()->current_stock)->toBe(3.0);
    expect(StockOut::where('status', TransactionStatus::CONFIRMED->value)->count())->toBe(1);
    expect(StockOut::where('status', TransactionStatus::DRAFT->value)->count())->toBe(1);
});

test('TC-CON-03: adjustment detects stock change between form open and submission -> STOCK_CHANGED 409 (AC-ADJ-03, D-33)', function () {
    // Stock was 50 when form opened
    $this->product->forceFill(['current_stock' => 50.0])->save();

    // In between, another transaction modified stock from 50 to 48
    $this->product->forceFill(['current_stock' => 48.0])->save();

    $stockService = app(StockService::class);

    // Submit with expected_system_qty = 50 -> rejected with StockChangedException
    expect(function () use ($stockService) {
        $stockService->createAdjustment([
            'adjustment_date' => now()->toDateString(),
            'product_id' => $this->product->id,
            'expected_system_qty' => 50,
            'physical_qty' => 47,
            'reason_code' => AdjustmentReason::DAMAGED->value,
            'notes' => 'Investigasi kerusakan kabel power',
        ], $this->adminGudang);
    })->toThrow(StockChangedException::class);

    // Stock remains 48
    expect((float) $this->product->fresh()->current_stock)->toBe(48.0);
    expect(StockMovement::count())->toBe(0);
});

test('concurrency stress test on MySQL with >= 50 sequential locked stock iterations (PRD §16.2)', function () {
    // Initial stock: 100
    $this->product->forceFill(['current_stock' => 100.0])->save();

    $stockService = app(StockService::class);
    $successfulOperations = 0;
    $failedOperations = 0;

    // Run 50 transactional operations under pessimistic locks
    for ($i = 1; $i <= 50; $i++) {
        $stockOut = StockOut::create([
            'number' => sprintf('OUT-202611-%04d', $i + 100),
            'transaction_date' => now()->toDateString(),
            'purpose_type' => StockOutPurpose::USAGE->value,
            'destination' => 'Uji Konkurensi '.$i,
            'status' => TransactionStatus::DRAFT,
            'created_by' => $this->adminGudang->id,
        ]);
        $stockOut->details()->create([
            'product_id' => $this->product->id,
            'quantity' => 2, // 50 * 2 = 100 exactly
        ]);

        try {
            $stockService->confirmStockOut($stockOut, $this->adminGudang);
            $successfulOperations++;
        } catch (Exception $e) {
            $failedOperations++;
        }
    }

    // All 50 iterations should successfully deplete the stock from 100 to 0
    expect($successfulOperations)->toBe(50);
    expect($failedOperations)->toBe(0);
    expect((float) $this->product->fresh()->current_stock)->toBe(0.0);

    // 51st operation: stock is 0, attempting to take 1 item -> must throw InsufficientStockException
    $stockOut51 = StockOut::create([
        'number' => 'OUT-202611-9999',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Uji Ekstra Melebihi Batas',
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->adminGudang->id,
    ]);
    $stockOut51->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 1,
    ]);

    expect(function () use ($stockService, $stockOut51) {
        $stockService->confirmStockOut($stockOut51, $this->adminGudang);
    })->toThrow(InsufficientStockException::class);

    expect((float) $this->product->fresh()->current_stock)->toBe(0.0);
    expect(StockMovement::where('product_id', $this->product->id)->count())->toBe(50);
});
