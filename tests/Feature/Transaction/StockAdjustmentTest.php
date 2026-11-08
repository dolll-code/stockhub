<?php

use App\Enums\AdjustmentReason;
use App\Enums\MovementType;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAdjustment;
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

    $this->category = Category::create(['name' => 'Komponen', 'is_active' => true]);
    $this->unitPcs = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);

    $this->product = Product::factory()->create([
        'code' => 'BRG-0050',
        'name' => 'Kabel Power',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'is_active' => true,
    ]);
});

test('TC-ADJ-01: adjustment with difference -3 (50 -> 47) updates stock, creates adjustment row and movement (AC-ADJ-01, FR-ADJ-01)', function () {
    $this->product->forceFill(['current_stock' => 50.0])->save();

    $response = $this->actingAs($this->adminGudang)->post('/adjustments', [
        'adjustment_date' => now()->toDateString(),
        'product_id' => $this->product->id,
        'expected_system_qty' => 50,
        'physical_qty' => 47,
        'reason_code' => AdjustmentReason::DAMAGED->value,
        'notes' => 'Ditemukan 3 pcs kabel power rusak terkelupas di rak B2',
    ]);

    $adjustment = StockAdjustment::where('product_id', $this->product->id)->firstOrFail();
    $response->assertRedirect("/adjustments/{$adjustment->id}");

    expect($adjustment->number)->toStartWith('ADJ-');
    expect((float) $adjustment->system_qty)->toBe(50.0);
    expect((float) $adjustment->physical_qty)->toBe(47.0);
    expect((float) $adjustment->difference)->toBe(-3.0);
    expect($adjustment->reason_code)->toBe(AdjustmentReason::DAMAGED);
    expect($adjustment->created_by)->toBe($this->adminGudang->id);

    // Stock must be 47
    expect((float) $this->product->fresh()->current_stock)->toBe(47.0);

    // Movement verified
    $movement = StockMovement::where('product_id', $this->product->id)->firstOrFail();
    expect($movement->movement_type)->toBe(MovementType::ADJUSTMENT);
    expect((float) $movement->qty_before)->toBe(50.0);
    expect((float) $movement->qty_change)->toBe(-3.0);
    expect((float) $movement->qty_after)->toBe(47.0);
    expect($movement->user_id)->toBe($this->adminGudang->id);

    // Audit log verified
    $log = ActivityLog::where('action', 'adjustment.created')->where('subject_id', $adjustment->id)->first();
    expect($log)->not->toBeNull();
});

test('TC-ADJ-02: adjustment with difference +2 (10 -> 12) increases stock to 12 (AC-ADJ-02)', function () {
    $this->product->forceFill(['current_stock' => 10.0])->save();

    $response = $this->actingAs($this->superAdmin)->post('/adjustments', [
        'adjustment_date' => now()->toDateString(),
        'product_id' => $this->product->id,
        'expected_system_qty' => 10,
        'physical_qty' => 12,
        'reason_code' => AdjustmentReason::FOUND->value,
        'notes' => 'Ditemukan 2 pcs kelebihan fisik barang belum tercatat',
    ]);

    $adjustment = StockAdjustment::where('product_id', $this->product->id)->firstOrFail();
    $response->assertRedirect("/adjustments/{$adjustment->id}");

    expect((float) $adjustment->difference)->toBe(2.0);
    expect((float) $this->product->fresh()->current_stock)->toBe(12.0);

    $movement = StockMovement::where('product_id', $this->product->id)->firstOrFail();
    expect((float) $movement->qty_before)->toBe(10.0);
    expect((float) $movement->qty_change)->toBe(2.0);
    expect((float) $movement->qty_after)->toBe(12.0);
});

test('TC-ADJ-03: adjustment with difference 0 is rejected with NO_DIFFERENCE (AC-ADJ-04, FR-ADJ-01)', function () {
    $this->product->forceFill(['current_stock' => 20.0])->save();

    $response = $this->actingAs($this->adminGudang)->post('/adjustments', [
        'adjustment_date' => now()->toDateString(),
        'product_id' => $this->product->id,
        'expected_system_qty' => 20,
        'physical_qty' => 20,
        'reason_code' => AdjustmentReason::COUNT_ERROR->value,
        'notes' => 'Tidak ada perbedaan stok fisik sama dengan sistem',
    ]);

    $response->assertSessionHasErrors('physical_qty');
    $errors = session('errors')->all();
    expect($errors[0])->toContain('Stok fisik sama dengan stok sistem');

    expect(StockAdjustment::count())->toBe(0);
    expect(StockMovement::count())->toBe(0);
});

test('TC-ADJ-04: adjustments have no edit or delete routes; SG and MG forbidden 403 on create/store (AC-ADJ-05, AC-ADJ-06, BR-16)', function () {
    // 1. Staff Gudang access denied
    $this->actingAs($this->staffGudang)->get('/adjustments/create')->assertForbidden();
    $this->actingAs($this->staffGudang)->post('/adjustments', [
        'adjustment_date' => now()->toDateString(),
        'product_id' => $this->product->id,
        'expected_system_qty' => 0,
        'physical_qty' => 5,
        'reason_code' => AdjustmentReason::FOUND->value,
        'notes' => 'Ilegal post oleh SG yang tidak berhak',
    ])->assertForbidden();

    // 2. Manajer access denied on create/store
    $this->actingAs($this->manajer)->get('/adjustments/create')->assertForbidden();
    $this->actingAs($this->manajer)->post('/adjustments', [])->assertForbidden();

    // 3. Manajer CAN view adjustment list
    $this->actingAs($this->manajer)->get('/adjustments')->assertOk();

    // 4. Verify no edit, update, or destroy routes exist for adjustments (rejected 404/405)
    expect(Route::has('adjustments.edit'))->toBeFalse();
    expect(Route::has('adjustments.update'))->toBeFalse();
    expect(Route::has('adjustments.destroy'))->toBeFalse();

    $adj = StockAdjustment::create([
        'number' => 'ADJ-202611-0001',
        'adjustment_date' => now()->toDateString(),
        'product_id' => $this->product->id,
        'system_qty' => 0,
        'physical_qty' => 5,
        'difference' => 5,
        'reason_code' => AdjustmentReason::FOUND->value,
        'notes' => 'Adjustment catatan awal',
        'created_by' => $this->adminGudang->id,
    ]);

    $this->actingAs($this->superAdmin)->get("/adjustments/{$adj->id}/edit")->assertNotFound();
    $responsePut = $this->actingAs($this->superAdmin)->put("/adjustments/{$adj->id}", []);
    expect(in_array($responsePut->getStatusCode(), [404, 405], true))->toBeTrue();

    $responseDelete = $this->actingAs($this->superAdmin)->delete("/adjustments/{$adj->id}");
    expect(in_array($responseDelete->getStatusCode(), [404, 405], true))->toBeTrue();
});
