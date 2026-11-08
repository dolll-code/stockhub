<?php

use App\Enums\MovementType;
use App\Enums\StockInSource;
use App\Enums\TransactionStatus;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockMovement;
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
    $this->supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'PT Mitra Sejati', 'is_active' => true]);

    $this->product = Product::factory()->create([
        'code' => 'BRG-0001',
        'name' => 'Mouse Wireless',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'main_supplier_id' => $this->supplier->id,
        'purchase_price' => 50000.00,
        'is_active' => true,
    ]);
});

test('TC-STI-01: saving or updating draft stock-in does NOT alter current_stock (AC-STI-01, BR-07)', function () {
    // Initial current stock is 0
    expect((float) $this->product->fresh()->current_stock)->toBe(0.0);

    // 1. Create draft
    $response = $this->actingAs($this->staffGudang1)->post('/stock-ins', [
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::PURCHASE->value,
        'supplier_id' => $this->supplier->id,
        'reference_document' => 'SJ-12345',
        'notes' => 'Penerimaan barang PO-01',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 20,
            ],
        ],
    ]);

    $stockIn = StockIn::where('reference_document', 'SJ-12345')->firstOrFail();
    $response->assertRedirect("/stock-ins/{$stockIn->id}");
    expect($stockIn->status)->toBe(TransactionStatus::DRAFT);
    expect($stockIn->number)->toStartWith('IN-');

    // Stock must remain 0
    expect((float) $this->product->fresh()->current_stock)->toBe(0.0);
    expect(StockMovement::where('product_id', $this->product->id)->count())->toBe(0);

    // 2. Update draft quantity to 35
    $responseUpdate = $this->actingAs($this->staffGudang1)->put("/stock-ins/{$stockIn->id}", [
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::PURCHASE->value,
        'supplier_id' => $this->supplier->id,
        'reference_document' => 'SJ-12345',
        'items' => [
            [
                'product_id' => $this->product->id,
                'quantity' => 35,
            ],
        ],
    ]);

    $responseUpdate->assertRedirect("/stock-ins/{$stockIn->id}");
    expect((float) $this->product->fresh()->current_stock)->toBe(0.0);
    expect(StockMovement::where('product_id', $this->product->id)->count())->toBe(0);
});

test('TC-STI-02: confirming stock-in adds stock (10 + 20 = 30) and creates stock_in movement (AC-STI-02, FR-STI-03)', function () {
    // Give initial stock 10
    $this->product->forceFill(['current_stock' => 10.0])->save();

    // Create draft for +20
    $stockIn = StockIn::create([
        'number' => 'IN-202611-0001',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::DRAFT,
        'total_amount' => 1000000.00,
        'created_by' => $this->staffGudang1->id,
    ]);
    $stockIn->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 20,
        'unit_price' => 50000.00,
    ]);

    // Confirm draft
    $response = $this->actingAs($this->staffGudang1)->post("/stock-ins/{$stockIn->id}/confirm");
    $response->assertRedirect("/stock-ins/{$stockIn->id}");

    $freshStockIn = $stockIn->fresh();
    expect($freshStockIn->status)->toBe(TransactionStatus::CONFIRMED);
    expect($freshStockIn->confirmed_by)->toBe($this->staffGudang1->id);
    expect($freshStockIn->confirmed_at)->not->toBeNull();

    // Stock should be 30
    expect((float) $this->product->fresh()->current_stock)->toBe(30.0);

    // Movement verified
    $movement = StockMovement::where('product_id', $this->product->id)->firstOrFail();
    expect($movement->movement_type)->toBe(MovementType::STOCK_IN);
    expect((float) $movement->qty_before)->toBe(10.0);
    expect((float) $movement->qty_change)->toBe(20.0);
    expect((float) $movement->qty_after)->toBe(30.0);
    expect($movement->user_id)->toBe($this->staffGudang1->id);

    // Log verified
    $log = ActivityLog::where('action', 'stock_in.confirmed')
        ->where('subject_id', $stockIn->id)
        ->first();
    expect($log)->not->toBeNull();
});

test('TC-STI-03: atomic rollback if any line is invalid or inactive product (AC-STI-03, BR-08)', function () {
    $p2 = Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'is_active' => false, // Inactive product on row 2
    ]);
    $p3 = Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'is_active' => true,
    ]);

    $stockIn = StockIn::create([
        'number' => 'IN-202611-0002',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->adminGudang->id,
    ]);
    $stockIn->details()->createMany([
        ['product_id' => $this->product->id, 'quantity' => 5],
        ['product_id' => $p2->id, 'quantity' => 10],
        ['product_id' => $p3->id, 'quantity' => 15],
    ]);

    $response = $this->actingAs($this->adminGudang)->post("/stock-ins/{$stockIn->id}/confirm");
    $response->assertSessionHasErrors('items.1.product_id');

    // Document must remain draft
    expect($stockIn->fresh()->status)->toBe(TransactionStatus::DRAFT);

    // No stocks changed
    expect((float) $this->product->fresh()->current_stock)->toBe(0.0);
    expect((float) $p2->fresh()->current_stock)->toBe(0.0);
    expect((float) $p3->fresh()->current_stock)->toBe(0.0);

    // No movements created
    expect(StockMovement::count())->toBe(0);
});

test('TC-STI-04: edit or delete on confirmed transaction is rejected with 409 INVALID_STATE_TRANSITION (AC-STI-04, BR-12)', function () {
    $stockIn = StockIn::create([
        'number' => 'IN-202611-0003',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::CONFIRMED,
        'confirmed_by' => $this->adminGudang->id,
        'confirmed_at' => now(),
        'created_by' => $this->adminGudang->id,
    ]);

    // Attempt update on confirmed document (HTML request)
    $response = $this->actingAs($this->adminGudang)->put("/stock-ins/{$stockIn->id}", [
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'items' => [['product_id' => $this->product->id, 'quantity' => 5]],
    ]);

    $response->assertStatus(409);
    $response->assertSee('409');

    // Attempt update via JSON request -> returns 409 JSON envelope
    $jsonResponse = $this->actingAs($this->adminGudang)->putJson("/stock-ins/{$stockIn->id}", [
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'items' => [['product_id' => $this->product->id, 'quantity' => 5]],
    ]);
    $jsonResponse->assertStatus(409);
    $jsonResponse->assertJsonFragment(['code' => 'INVALID_STATE_TRANSITION']);
});

test('TC-STI-05: cancelling confirmed stock-in creates reversal movement and reduces stock back (AC-STI-05, BR-13)', function () {
    // Current stock is 30 from prior transaction of +20
    $this->product->forceFill(['current_stock' => 30.0])->save();

    $stockIn = StockIn::create([
        'number' => 'IN-202611-0004',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::CONFIRMED,
        'confirmed_by' => $this->adminGudang->id,
        'confirmed_at' => now(),
        'created_by' => $this->adminGudang->id,
    ]);
    $stockIn->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 20,
    ]);

    // Admin Gudang cancels confirmed document with reason
    $response = $this->actingAs($this->adminGudang)->post("/stock-ins/{$stockIn->id}/cancel", [
        'cancel_reason' => 'Salah input barang masuk dari vendor A',
    ]);
    $response->assertRedirect("/stock-ins/{$stockIn->id}");

    $freshStockIn = $stockIn->fresh();
    expect($freshStockIn->status)->toBe(TransactionStatus::CANCELLED);
    expect($freshStockIn->cancelled_by)->toBe($this->adminGudang->id);
    expect($freshStockIn->cancel_reason)->toBe('Salah input barang masuk dari vendor A');

    // Stock should be reduced back to 10
    expect((float) $this->product->fresh()->current_stock)->toBe(10.0);

    // Reversal movement verified
    $reversal = StockMovement::where('movement_type', MovementType::STOCK_IN_CANCEL)->firstOrFail();
    expect((float) $reversal->qty_before)->toBe(30.0);
    expect((float) $reversal->qty_change)->toBe(-20.0);
    expect((float) $reversal->qty_after)->toBe(10.0);
});

test('TC-STI-06: cancelling confirmed stock-in rejected with REVERSAL_INSUFFICIENT_STOCK if stock insufficient (AC-STI-06, BR-13)', function () {
    // Stock is only 5 (already consumed by another operation), but stock-in was 20
    $this->product->forceFill(['current_stock' => 5.0])->save();

    $stockIn = StockIn::create([
        'number' => 'IN-202611-0005',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::CONFIRMED,
        'confirmed_by' => $this->adminGudang->id,
        'confirmed_at' => now(),
        'created_by' => $this->adminGudang->id,
    ]);
    $stockIn->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 20,
    ]);

    $response = $this->actingAs($this->adminGudang)->post("/stock-ins/{$stockIn->id}/cancel", [
        'cancel_reason' => 'Pembatalan transaksi salah catat',
    ]);

    $response->assertSessionHasErrors();
    $errors = session('errors')->all();
    expect($errors[0])->toContain('stok Mouse Wireless saat ini');
    expect($errors[0])->toContain('harus dikurangi');

    // Document remains confirmed and stock unchanged
    expect($stockIn->fresh()->status)->toBe(TransactionStatus::CONFIRMED);
    expect((float) $this->product->fresh()->current_stock)->toBe(5.0);
});

test('TC-STI-07: supplier is mandatory when source_type is purchase (AC-STI-08, BR-22)', function () {
    $response = $this->actingAs($this->staffGudang1)->post('/stock-ins', [
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::PURCHASE->value,
        'supplier_id' => null, // Missing supplier
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 5],
        ],
    ]);

    $response->assertSessionHasErrors('supplier_id');
});

test('TC-STI-08: duplicate product in single document is rejected by validation (AC-STI-10, BR-11)', function () {
    $response = $this->actingAs($this->staffGudang1)->post('/stock-ins', [
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'items' => [
            ['product_id' => $this->product->id, 'quantity' => 5],
            ['product_id' => $this->product->id, 'quantity' => 10], // Duplicate product
        ],
    ]);

    $response->assertSessionHasErrors(['items.0.product_id', 'items.1.product_id']);
});

test('TC-STI-09: document number is NOT reused after cancellation (AC-STI-11, BR-19)', function () {
    // Create draft
    $this->actingAs($this->adminGudang)->post('/stock-ins', [
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'items' => [['product_id' => $this->product->id, 'quantity' => 5]],
    ]);

    $firstDoc = StockIn::latest('id')->firstOrFail();
    $firstNumber = $firstDoc->number;

    // Cancel draft
    $this->actingAs($this->adminGudang)->post("/stock-ins/{$firstDoc->id}/cancel", [
        'cancel_reason' => 'Draft dibatalkan oleh petugas gudang',
    ]);
    expect($firstDoc->fresh()->status)->toBe(TransactionStatus::CANCELLED);

    // Create next document -> must have distinct sequential number
    $this->actingAs($this->adminGudang)->post('/stock-ins', [
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'items' => [['product_id' => $this->product->id, 'quantity' => 10]],
    ]);

    $secondDoc = StockIn::latest('id')->firstOrFail();
    expect($secondDoc->number)->not->toBe($firstNumber);
});

test('TC-STI-10: draft cancellation requires reason >= 10 chars and creates no movements (AC-STI-12, BR-14)', function () {
    $stockIn = StockIn::create([
        'number' => 'IN-202611-0010',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->staffGudang1->id,
    ]);
    $stockIn->details()->create([
        'product_id' => $this->product->id,
        'quantity' => 10,
    ]);

    // 1. Cancel with reason too short (< 10 chars)
    $responseShort = $this->actingAs($this->staffGudang1)->post("/stock-ins/{$stockIn->id}/cancel", [
        'cancel_reason' => 'Salah',
    ]);
    $responseShort->assertSessionHasErrors('cancel_reason');
    expect($stockIn->fresh()->status)->toBe(TransactionStatus::DRAFT);

    // 2. Cancel with valid reason >= 10 chars
    $responseValid = $this->actingAs($this->staffGudang1)->post("/stock-ins/{$stockIn->id}/cancel", [
        'cancel_reason' => 'Draft dibatalkan karena salah pilih barang masuk',
    ]);
    $responseValid->assertRedirect("/stock-ins/{$stockIn->id}");

    $fresh = $stockIn->fresh();
    expect($fresh->status)->toBe(TransactionStatus::CANCELLED);
    expect($fresh->cancel_reason)->toBe('Draft dibatalkan karena salah pilih barang masuk');
    expect(StockMovement::where('reference_id', $stockIn->id)->count())->toBe(0);
});

test('TC-AUTHZ-02: Staff Gudang cannot access or manage transactions of another staff (AC-STI-09, BR-23)', function () {
    // Transaction created by Staff 1
    $stockInStaff1 = StockIn::create([
        'number' => 'IN-202611-0020',
        'transaction_date' => now()->toDateString(),
        'source_type' => StockInSource::INTERNAL->value,
        'status' => TransactionStatus::DRAFT,
        'created_by' => $this->staffGudang1->id,
    ]);

    // Staff 2 attempts to view Staff 1's transaction -> 404
    $this->actingAs($this->staffGudang2)->get("/stock-ins/{$stockInStaff1->id}")
        ->assertNotFound();

    // Staff 2 attempts to confirm Staff 1's transaction -> 404
    $this->actingAs($this->staffGudang2)->post("/stock-ins/{$stockInStaff1->id}/confirm")
        ->assertNotFound();

    // Staff 2 attempts to cancel Staff 1's transaction -> 404
    $this->actingAs($this->staffGudang2)->post("/stock-ins/{$stockInStaff1->id}/cancel", [
        'cancel_reason' => 'Mencoba batalkan milik orang lain',
    ])->assertNotFound();

    // Admin Gudang can access and confirm
    $this->actingAs($this->adminGudang)->get("/stock-ins/{$stockInStaff1->id}")
        ->assertOk();
});
