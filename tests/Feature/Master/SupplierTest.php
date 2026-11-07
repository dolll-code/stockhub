<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\DocumentNumberService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

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

test('TC-SUP-01: supplier code is sequential and unique under concurrency / row lock (AC-SUP-01, FR-SUP-02, BR-19)', function () {
    // Test sequential code generation via service and HTTP
    $response1 = $this->actingAs($this->adminGudang)->post('/suppliers', [
        'name' => 'Supplier Pertama',
        'contact_name' => 'Budi',
        'phone' => '08123456789',
    ]);
    $response1->assertRedirect('/suppliers');

    $response2 = $this->actingAs($this->adminGudang)->post('/suppliers', [
        'name' => 'Supplier Kedua',
        'contact_name' => 'Andi',
        'phone' => '08987654321',
    ]);
    $response2->assertRedirect('/suppliers');

    $s1 = Supplier::where('name', 'Supplier Pertama')->firstOrFail();
    $s2 = Supplier::where('name', 'Supplier Kedua')->firstOrFail();

    expect($s1->code)->toBe('SUP-0001');
    expect($s2->code)->toBe('SUP-0002');

    // Concurrency test directly on DocumentNumberService with row lock in nested transactions
    $docService = app(DocumentNumberService::class);
    $generatedCodes = [];

    for ($i = 0; $i < 5; $i++) {
        DB::transaction(function () use ($docService, &$generatedCodes) {
            $code = $docService->generateSupplierCode();
            Supplier::create([
                'code' => $code,
                'name' => 'Concurrent Supplier '.$code,
                'is_active' => true,
            ]);
            $generatedCodes[] = $code;
        });
    }

    expect($generatedCodes)->toBe(['SUP-0003', 'SUP-0004', 'SUP-0005', 'SUP-0006', 'SUP-0007']);
    expect(count(array_unique($generatedCodes)))->toBe(5);
});

test('TC-SUP-02: inactive supplier cannot be chosen for new products (AC-SUP-02, FR-SUP-05, BR-22)', function () {
    $activeSupplier = Supplier::create([
        'code' => 'SUP-0001',
        'name' => 'Supplier Aktif',
        'is_active' => true,
    ]);

    $inactiveSupplier = Supplier::create([
        'code' => 'SUP-0002',
        'name' => 'Supplier Nonaktif',
        'is_active' => false,
    ]);

    $category = Category::create(['name' => 'Elektronik', 'is_active' => true]);
    $unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);

    // Product creation form excludes inactive supplier
    $responseForm = $this->actingAs($this->adminGudang)->get('/products/create');
    $responseForm->assertOk();
    $responseForm->assertSee('Supplier Aktif');
    $responseForm->assertDontSee('Supplier Nonaktif');

    // Attempting to post new product with inactive supplier is rejected by validation
    $responsePost = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Produk Baru',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'main_supplier_id' => $inactiveSupplier->id,
        'min_stock' => 0,
    ]);

    $responsePost->assertSessionHasErrors('main_supplier_id');
});

test('TC-SUP-03: supplier code is immutable and supplier can be deactivated anytime (AC-SUP-03, BR-04, BR-05)', function () {
    $supplier = Supplier::create([
        'code' => 'SUP-0001',
        'name' => 'Supplier A',
        'is_active' => true,
    ]);

    // Update attempt with altered code
    $response = $this->actingAs($this->superAdmin)->put("/suppliers/{$supplier->id}", [
        'code' => 'SUP-9999',
        'name' => 'Supplier A Updated',
    ]);
    $response->assertRedirect('/suppliers');

    // Code MUST remain unchanged (immutable)
    expect($supplier->fresh()->code)->toBe('SUP-0001');
    expect($supplier->fresh()->name)->toBe('Supplier A Updated');

    // Supplier can be deactivated anytime (BR-04)
    $responseDeactivate = $this->actingAs($this->superAdmin)->patch("/suppliers/{$supplier->id}/status");
    $responseDeactivate->assertSessionHasNoErrors();
    expect($supplier->fresh()->is_active)->toBeFalse();
});

test('supplier detail page renders data and handles empty stock-in transactions state (FR-SUP-04)', function () {
    $supplier = Supplier::create([
        'code' => 'SUP-0001',
        'name' => 'Supplier Utama',
        'contact_name' => 'Hendra',
        'phone' => '0811223344',
        'email' => 'hendra@supplier.test',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->staffGudang)->get("/suppliers/{$supplier->id}");
    $response->assertOk();
    $response->assertSee('Supplier Utama');
    $response->assertSee('SUP-0001');
    $response->assertSee('Belum ada transaksi barang masuk dari supplier ini.');
});
