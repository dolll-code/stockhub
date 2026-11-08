<?php

use App\Enums\MovementType;
use App\Enums\StockOutPurpose;
use App\Enums\TransactionStatus;
use App\Models\Category;
use App\Models\Product;
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

    $this->staffGudang1 = User::factory()->create();
    $this->staffGudang1->assignRole('staff_gudang');

    $this->staffGudang2 = User::factory()->create();
    $this->staffGudang2->assignRole('staff_gudang');

    $this->manajer = User::factory()->create();
    $this->manajer->assignRole('manajer');

    $this->category = Category::create(['name' => 'Periferal', 'is_active' => true]);
    $this->unitPcs = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);

    $this->product = Product::factory()->create([
        'code' => 'BRG-0010',
        'name' => 'Keyboard Mechanical',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'is_active' => true,
    ]);
});

test('TC-STO-01: confirming stock-out of 5 from 25 updates stock to 20 and records stock_out movement (AC-STO-01, FR-STO-03)', function () {
    // Current stock is 25
    $this->product->forceFill(['current_stock' => 25.0])->save();

    // Create draft
    $stockOut = StockOut::create([
        'number' => 'OUT-202611-0001',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Divisi Operasional Gudang',
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->staffGudang1->id,
    ]);
    $stockOut->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 5,
    ]);

    // Confirm
    $response = $this->actingAs($this->staffGudang1)->post("/stock-outs/{$stockOut->id}/confirm");
    $response->assertRedirect("/stock-outs/{$stockOut->id}");

    $freshStockOut = $stockOut->fresh();
    expect($freshStockOut->status)->toBe(TransactionStatus::CONFIRMED);
    expect($freshStockOut->confirmed_by)->toBe($this->staffGudang1->id);

    // Stock should be 20
    expect((float) $this->product->fresh()->current_stock)->toBe(20.0);

    // Movement verified
    $movement = StockMovement::where('product_id', $this->product->id)->firstOrFail();
    expect($movement->movement_type)->toBe(MovementType::STOCK_OUT);
    expect((float) $movement->qty_before)->toBe(25.0);
    expect((float) $movement->qty_change)->toBe(-5.0);
    expect((float) $movement->qty_after)->toBe(20.0);
    expect($movement->user_id)->toBe($this->staffGudang1->id);
    expect($movement->reference_number)->toBe('OUT-202611-0001');
});

test('TC-STO-02: draft allows quantity > stock but confirmation rejects with INSUFFICIENT_STOCK (AC-STO-02, BR-10)', function () {
    // Stock is 3
    $this->product->forceFill(['current_stock' => 3.0])->save();

    // 1. Create draft with quantity 5 (allowed with warning in UI)
    $responseCreate = $this->actingAs($this->staffGudang1)->post('/stock-outs', [
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Divisi IT',
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ],
    ]);

    $stockOut = StockOut::where('destination', 'Divisi IT')->firstOrFail();
    $responseCreate->assertRedirect("/stock-outs/{$stockOut->id}");
    expect($stockOut->status)->toBe(TransactionStatus::DRAFT);
    expect((float) $this->product->fresh()->current_stock)->toBe(3.0);

    // 2. Attempt confirm -> rejected with INSUFFICIENT_STOCK
    $responseConfirm = $this->actingAs($this->staffGudang1)->post("/stock-outs/{$stockOut->id}/confirm");
    $responseConfirm->assertSessionHasErrors('items.0.quantity');
    $errors = session('errors')->all();
    expect($errors[0])->toContain('Stok Keyboard Mechanical tersisa 3 pcs, diminta 5 pcs');

    // Document remains draft, stock remains 3
    expect($stockOut->fresh()->status)->toBe(TransactionStatus::DRAFT);
    expect((float) $this->product->fresh()->current_stock)->toBe(3.0);
});

test('TC-STO-03: cancelling confirmed stock-out increases stock back via reversal movement (AC-STO-04, FR-STO-05)', function () {
    // Stock is 20 after output of 5
    $this->product->forceFill(['current_stock' => 20.0])->save();

    $stockOut = StockOut::create([
        'number' => 'OUT-202611-0003',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Ruang Server',
        'status' => TransactionStatus::CONFIRMED,
        'confirmed_by' => $this->adminGudang->id,
        'confirmed_at' => now(),
        'created_by' => $this->adminGudang->id,
    ]);
    $stockOut->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 5,
    ]);

    // Admin cancels confirmed document
    $response = $this->actingAs($this->adminGudang)->post("/stock-outs/{$stockOut->id}/cancel", [
        'cancel_reason' => 'Pengeluaran keyboard dibatalkan karena salah tipe',
    ]);
    $response->assertRedirect("/stock-outs/{$stockOut->id}");

    $fresh = $stockOut->fresh();
    expect($fresh->status)->toBe(TransactionStatus::CANCELLED);
    expect($fresh->cancel_reason)->toBe('Pengeluaran keyboard dibatalkan karena salah tipe');

    // Stock returns to 25
    expect((float) $this->product->fresh()->current_stock)->toBe(25.0);

    // Reversal movement verified
    $reversal = StockMovement::where('movement_type', MovementType::STOCK_OUT_CANCEL)->firstOrFail();
    expect((float) $reversal->qty_before)->toBe(20.0);
    expect((float) $reversal->qty_change)->toBe(5.0);
    expect((float) $reversal->qty_after)->toBe(25.0);
});

test('TC-STO-04: draft with product inactivated before confirmation fails with PRODUCT_INACTIVE (AC-STO-05, BR-04)', function () {
    $this->product->forceFill(['current_stock' => 0.0, 'is_active' => false])->save();

    $stockOut = StockOut::create([
        'number' => 'OUT-202611-0004',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Divisi HR',
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->adminGudang->id,
    ]);
    $stockOut->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 2,
    ]);

    $response = $this->actingAs($this->adminGudang)->post("/stock-outs/{$stockOut->id}/confirm");
    $response->assertSessionHasErrors('items.0.product_id');

    expect($stockOut->fresh()->status)->toBe(TransactionStatus::DRAFT);
});

test('TC-STO-05: AJAX products search and stock endpoints return real-time stock and unit (AC-STO-06, FR-STO-01)', function () {
    $this->product->forceFill(['current_stock' => 15.0])->save();

    // 1. Single product stock endpoint
    $responseStock = $this->actingAs($this->staffGudang1)->getJson("/ajax/products/{$this->product->id}/stock");
    $responseStock->assertOk();
    $responseStock->assertJson([
        'data' => [
            'id' => $this->product->id,
            'code' => 'BRG-0010',
            'current_stock' => '15.000',
            'unit' => [
                'symbol' => 'pcs',
                'allow_decimal' => false,
            ],
        ],
    ]);
    // Ensure price is NOT returned for SG
    $responseStock->assertJsonMissing(['purchase_price']);

    // 2. Search endpoint
    $responseSearch = $this->actingAs($this->staffGudang1)->getJson('/ajax/products/search?q=Key');
    $responseSearch->assertOk();
    $responseSearch->assertJsonFragment([
        'id' => $this->product->id,
        'code' => 'BRG-0010',
    ]);
    $responseSearch->assertJsonMissing(['purchase_price']);
});

test('TC-STO-06: database failure during stock-out confirmation triggers complete rollback (AC-STO-07, BR-08)', function () {
    $this->product->forceFill(['current_stock' => 10.0])->save();

    $stockOut = StockOut::create([
        'number' => 'OUT-202611-0006',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Testing Fail',
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->adminGudang->id,
    ]);
    $stockOut->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 5,
    ]);

    // Mock stock service to throw
    $this->mock(StockService::class, function ($mock) {
        $mock->shouldReceive('confirmStockOut')
            ->once()
            ->andThrow(new RuntimeException('Simulated DB connection drop during confirm'));
    });

    $this->withoutExceptionHandling();

    expect(function () use ($stockOut) {
        $this->actingAs($this->adminGudang)->post("/stock-outs/{$stockOut->id}/confirm");
    })->toThrow(RuntimeException::class);

    // Stock remains 10
    expect((float) $this->product->fresh()->current_stock)->toBe(10.0);
    expect($stockOut->fresh()->status)->toBe(TransactionStatus::DRAFT);
    expect(StockMovement::count())->toBe(0);
});

test('TC-STO-07: draft cancellation requires reason and non-creator SG is 404 (AC-STO-08, BR-14, BR-23)', function () {
    $stockOut = StockOut::create([
        'number' => 'OUT-202611-0007',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => StockOutPurpose::USAGE->value,
        'destination' => 'Staff 1 Project',
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->staffGudang1->id,
    ]);

    // Other SG attempts to cancel -> 404
    $this->actingAs($this->staffGudang2)->post("/stock-outs/{$stockOut->id}/cancel", [
        'cancel_reason' => 'Dibatalkan oleh staf lain',
    ])->assertNotFound();

    // Creator cancels with valid reason
    $response = $this->actingAs($this->staffGudang1)->post("/stock-outs/{$stockOut->id}/cancel", [
        'cancel_reason' => 'Draft barang keluar dibatalkan oleh pembuat',
    ]);
    $response->assertRedirect("/stock-outs/{$stockOut->id}");
    expect($stockOut->fresh()->status)->toBe(TransactionStatus::CANCELLED);
    expect(StockMovement::count())->toBe(0);
});
