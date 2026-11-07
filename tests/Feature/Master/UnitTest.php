<?php

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->adminGudang = User::factory()->create();
    $this->adminGudang->assignRole('admin_gudang');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('staff_gudang');

    $this->manajer = User::factory()->create();
    $this->manajer->assignRole('manajer');
});

test('TC-UNT-01: allow_decimal cannot be modified once unit is used by products (AC-UNT-01, BR-25)', function () {
    $unit = Unit::create([
        'name' => 'Kilogram',
        'symbol' => 'kg',
        'allow_decimal' => true,
        'is_active' => true,
    ]);
    $category = Category::create(['name' => 'Komoditas', 'is_active' => true]);

    Product::factory()->create([
        'unit_id' => $unit->id,
        'category_id' => $category->id,
    ]);

    // SA attempts to change allow_decimal to false (0)
    $response = $this->actingAs($this->superAdmin)->put("/units/{$unit->id}", [
        'name' => 'Kilogram',
        'symbol' => 'kg',
        'allow_decimal' => 0,
    ]);

    $response->assertSessionHasErrors('allow_decimal');
    $errors = session('errors')->get('allow_decimal');
    expect($errors[0])->toContain('tidak dapat diubah karena satuan sudah digunakan');
    expect($unit->fresh()->allow_decimal)->toBeTrue();
});

test('TC-UNT-02: decimal quantities for non-decimal units are rejected (AC-UNT-02, BR-01)', function () {
    $pcs = Unit::create([
        'name' => 'Pieces',
        'symbol' => 'pcs',
        'allow_decimal' => false,
        'is_active' => true,
    ]);
    $category = Category::create(['name' => 'Aksesoris', 'is_active' => true]);

    // Attempt to create product with 1.5 opening_stock for non-decimal unit
    $response = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Mouse Optik Non-Decimal',
        'category_id' => $category->id,
        'unit_id' => $pcs->id,
        'min_stock' => 5,
        'opening_stock' => 1.5,
    ]);

    $response->assertSessionHasErrors('opening_stock');
    $errors = session('errors')->get('opening_stock');
    expect($errors[0])->toContain('harus bilangan bulat');

    // Attempt to create product with 2.5 min_stock for non-decimal unit
    $responseMin = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Mouse Optik Non-Decimal Min',
        'category_id' => $category->id,
        'unit_id' => $pcs->id,
        'min_stock' => 2.5,
        'opening_stock' => 10,
    ]);

    $responseMin->assertSessionHasErrors('min_stock');
});

test('unit cannot be deactivated if used by active products (BR-04)', function () {
    $unit = Unit::create([
        'name' => 'Meter',
        'symbol' => 'm',
        'allow_decimal' => true,
        'is_active' => true,
    ]);
    $category = Category::create(['name' => 'Kabel', 'is_active' => true]);

    Product::factory()->create([
        'unit_id' => $unit->id,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->superAdmin)->patch("/units/{$unit->id}/status");
    $response->assertSessionHasErrors('status');
    expect($unit->fresh()->is_active)->toBeTrue();
});

test('unit actions record activity logs and enforce authorization', function () {
    $response = $this->actingAs($this->superAdmin)->post('/units', [
        'name' => 'Dozen',
        'symbol' => 'dzn',
        'allow_decimal' => 0,
        'description' => '12 pieces',
    ]);
    $response->assertRedirect('/units');

    $unit = Unit::where('symbol', 'dzn')->firstOrFail();

    $log = ActivityLog::where('subject_type', $unit->getMorphClass())
        ->where('subject_id', $unit->id)
        ->where('action', 'unit.created')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($this->superAdmin->id);

    // SG and MG cannot create unit
    $this->actingAs($this->staffGudang)->post('/units', ['name' => 'Fail', 'symbol' => 'f'])->assertForbidden();
    $this->actingAs($this->manajer)->post('/units', ['name' => 'Fail2', 'symbol' => 'f2'])->assertForbidden();
});
