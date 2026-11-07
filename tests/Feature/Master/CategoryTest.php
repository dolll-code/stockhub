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

test('TC-CAT-01: duplicate category name case-insensitive is rejected (AC-CAT-01, FR-CAT-02)', function () {
    Category::create(['name' => 'Elektronik', 'is_active' => true]);

    $response = $this->actingAs($this->superAdmin)->post('/categories', [
        'name' => 'elektronik',
        'description' => 'Kategori peralatan elektronik',
    ]);

    $response->assertSessionHasErrors('name');
    expect(Category::count())->toBe(1);
});

test('TC-CAT-02: deactivating category used by active products is rejected with active product count (AC-CAT-02, BR-04)', function () {
    $category = Category::create(['name' => 'Bahan Baku', 'is_active' => true]);
    $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'allow_decimal' => true, 'is_active' => true]);

    Product::factory()->count(3)->create([
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'is_active' => true,
    ]);

    // Attempt to deactivate via status endpoint
    $response = $this->actingAs($this->adminGudang)->patch("/categories/{$category->id}/status");

    $response->assertSessionHasErrors('status');
    $errors = session('errors')->get('status');
    expect($errors[0])->toContain('Kategori masih dipakai 3 barang aktif');
    expect($category->fresh()->is_active)->toBeTrue();

    // Attempt to deactivate via update endpoint
    $responseUpdate = $this->actingAs($this->adminGudang)->put("/categories/{$category->id}", [
        'name' => 'Bahan Baku Updated',
        'is_active' => false,
    ]);
    $responseUpdate->assertSessionHasErrors('is_active');
    expect($category->fresh()->is_active)->toBeTrue();
});

test('category deactivation succeeds when all linked products are inactive and reactivation is always allowed (BR-04)', function () {
    $category = Category::create(['name' => 'Komponen Pasif', 'is_active' => true]);
    $unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);

    // Product linked is inactive
    Product::factory()->inactive()->create([
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    // Deactivation should succeed
    $response = $this->actingAs($this->superAdmin)->patch("/categories/{$category->id}/status");
    $response->assertSessionHasNoErrors();
    expect($category->fresh()->is_active)->toBeFalse();

    // Re-activation should always succeed (BR-04)
    $responseReactivate = $this->actingAs($this->superAdmin)->patch("/categories/{$category->id}/status");
    $responseReactivate->assertSessionHasNoErrors();
    expect($category->fresh()->is_active)->toBeTrue();
});

test('TC-CAT-03: inactive category is excluded from new product selection while existing products retain category (AC-CAT-03, BR-03)', function () {
    $activeCategory = Category::create(['name' => 'Kategori Aktif', 'is_active' => true]);
    $inactiveCategory = Category::create(['name' => 'Kategori Nonaktif', 'is_active' => false]);
    $unit = Unit::create(['name' => 'Unit', 'symbol' => 'unit', 'allow_decimal' => false, 'is_active' => true]);

    // Create existing product with inactive category
    $existingProduct = Product::factory()->create([
        'category_id' => $inactiveCategory->id,
        'unit_id' => $unit->id,
    ]);

    // View create form: inactiveCategory not available in selection
    $responseCreate = $this->actingAs($this->adminGudang)->get('/products/create');
    $responseCreate->assertOk();
    $responseCreate->assertSee('Kategori Aktif');
    $responseCreate->assertDontSee('Kategori Nonaktif');

    // Existing product still renders the inactive category name
    $responseShow = $this->actingAs($this->staffGudang)->get("/products/{$existingProduct->id}");
    $responseShow->assertOk();
    $responseShow->assertSee('Kategori Nonaktif');
});

test('category actions record audit logs via ActivityLogger', function () {
    $response = $this->actingAs($this->superAdmin)->post('/categories', [
        'name' => 'Kategori Audit',
        'description' => 'Test audit log',
    ]);
    $response->assertRedirect('/categories');

    $category = Category::where('name', 'Kategori Audit')->firstOrFail();

    $log = ActivityLog::where('subject_type', $category->getMorphClass())
        ->where('subject_id', $category->id)
        ->where('action', 'category.created')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($this->superAdmin->id);
    expect($log->new_values['name'])->toBe('Kategori Audit');
});

test('category authorization rules: SG and MG can view list but cannot create or modify', function () {
    Category::create(['name' => 'ATK', 'is_active' => true]);

    // Staff Gudang can view list
    $this->actingAs($this->staffGudang)->get('/categories')->assertOk();

    // Staff Gudang cannot access create form or post
    $this->actingAs($this->staffGudang)->get('/categories/create')->assertForbidden();
    $this->actingAs($this->staffGudang)->post('/categories', ['name' => 'Illegal'])->assertForbidden();

    // Manajer can view list but cannot create
    $this->actingAs($this->manajer)->get('/categories')->assertOk();
    $this->actingAs($this->manajer)->get('/categories/create')->assertForbidden();
});
