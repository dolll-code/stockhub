<?php

use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\DocumentNumberService;
use App\Services\ProductService;
use App\Services\StockService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    $this->category = Category::create(['name' => 'Elektronik', 'is_active' => true]);
    $this->unitPcs = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);
    $this->unitKg = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'allow_decimal' => true, 'is_active' => true]);
    $this->supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'PT Mitra Sejati', 'is_active' => true]);
});

test('TC-PRD-01: creating product with opening_stock > 0 creates product and exactly one opening_balance movement in single transaction (AC-PRD-01, BR-17)', function () {
    $response = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Kabel UTP Cat6',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'main_supplier_id' => $this->supplier->id,
        'purchase_price' => 250000,
        'min_stock' => 5,
        'opening_stock' => 20,
    ]);

    $response->assertRedirect('/products');

    $product = Product::where('name', 'Kabel UTP Cat6')->firstOrFail();
    expect($product->code)->toBe('BRG-0001');
    expect((float) $product->current_stock)->toBe(20.0);

    // Verify exactly one movement was created
    $movements = StockMovement::where('product_id', $product->id)->get();
    expect($movements->count())->toBe(1);

    $movement = $movements->first();
    expect($movement->movement_type)->toBe(MovementType::OPENING_BALANCE);
    expect((float) $movement->qty_before)->toBe(0.0);
    expect((float) $movement->qty_change)->toBe(20.0);
    expect((float) $movement->qty_after)->toBe(20.0);
    expect($movement->user_id)->toBe($this->adminGudang->id);
});

test('TC-PRD-02: opening_stock = 0 does not create movement; movement failure triggers full rollback (AC-PRD-02)', function () {
    // 1. Zero opening stock: no movement created
    $responseZero = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Barang Tanpa Saldo Awal',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 2,
        'opening_stock' => 0,
    ]);
    $responseZero->assertRedirect('/products');

    $productZero = Product::where('name', 'Barang Tanpa Saldo Awal')->firstOrFail();
    expect((float) $productZero->current_stock)->toBe(0.0);
    expect(StockMovement::where('product_id', $productZero->id)->count())->toBe(0);

    // 2. Mocking StockService to throw exception on opening balance -> verify rollback
    $mockStock = Mockery::mock(StockService::class);
    $mockStock->shouldReceive('recordOpeningBalance')
        ->once()
        ->andThrow(new RuntimeException('Simulated stock service DB failure'));

    $productService = new ProductService(
        app(DocumentNumberService::class),
        $mockStock,
        app(ActivityLogger::class)
    );
    $this->app->instance(ProductService::class, $productService);

    $this->withoutExceptionHandling();

    expect(function () {
        $this->actingAs($this->adminGudang)->post('/products', [
            'name' => 'Barang Gagal Simpan',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'min_stock' => 1,
            'opening_stock' => 10,
        ]);
    })->toThrow(RuntimeException::class);

    // Rollback verified: product was NOT persisted
    expect(Product::where('name', 'Barang Gagal Simpan')->exists())->toBeFalse();
    expect(StockMovement::where('qty_change', 10)->exists())->toBeFalse();
});

test('TC-PRD-03: product code is immutable after saved (AC-PRD-03, BR-05)', function () {
    $product = Product::factory()->create([
        'code' => 'BRG-0100',
        'name' => 'Nama Awal',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
    ]);

    // Attempt to update code
    $response = $this->actingAs($this->superAdmin)->put("/products/{$product->id}", [
        'code' => 'BRG-9999',
        'name' => 'Nama Berubah',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 5,
    ]);

    $response->assertRedirect('/products');
    $fresh = $product->fresh();
    expect($fresh->code)->toBe('BRG-0100');
    expect($fresh->name)->toBe('Nama Berubah');
});

test('manual product code format regex and uppercasing are strictly enforced (FR-PRD-02)', function () {
    // Valid manual code with lower case -> automatically converted to uppercase
    $responseValid = $this->actingAs($this->adminGudang)->post('/products', [
        'code' => 'sku.123_abc-01',
        'name' => 'Barang SKU Manual',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 0,
    ]);
    $responseValid->assertRedirect('/products');

    $product = Product::where('name', 'Barang SKU Manual')->firstOrFail();
    expect($product->code)->toBe('SKU.123_ABC-01');

    // Invalid manual code (special characters or too short)
    $responseInvalid = $this->actingAs($this->adminGudang)->post('/products', [
        'code' => 'X$', // Invalid regex
        'name' => 'Barang Invalid Regex',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 0,
    ]);
    $responseInvalid->assertSessionHasErrors('code');
});

test('TC-PRD-04: deactivating product with stock > 0 is rejected (AC-PRD-04, BR-04)', function () {
    $productWithStock = Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'is_active' => true,
    ]);
    // Set stock via forceFill to simulate stock presence
    $productWithStock->forceFill(['current_stock' => 5.0])->save();

    $response = $this->actingAs($this->adminGudang)->patch("/products/{$productWithStock->id}/status");
    $response->assertSessionHasErrors('status');
    $errors = session('errors')->get('status');
    expect($errors[0])->toContain('Stok harus 0');
    expect($productWithStock->fresh()->is_active)->toBeTrue();

    // When stock is 0, deactivation is permitted
    $productZeroStock = Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'is_active' => true,
    ]);
    $responseZero = $this->actingAs($this->adminGudang)->patch("/products/{$productZeroStock->id}/status");
    $responseZero->assertSessionHasNoErrors();
    expect($productZeroStock->fresh()->is_active)->toBeFalse();
});

test('TC-PRD-05: unit cannot be changed if product already has movements (AC-PRD-05, BR-25)', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
    ]);

    // Insert dummy movement
    StockMovement::create([
        'product_id' => $product->id,
        'movement_type' => MovementType::OPENING_BALANCE,
        'movement_date' => now()->toDateString(),
        'qty_before' => 0,
        'qty_change' => 10,
        'qty_after' => 10,
        'user_id' => $this->adminGudang->id,
    ]);

    // Attempt to change unit from Pcs to Kg
    $response = $this->actingAs($this->adminGudang)->put("/products/{$product->id}", [
        'name' => $product->name,
        'category_id' => $this->category->id,
        'unit_id' => $this->unitKg->id,
        'min_stock' => 5,
    ]);

    $response->assertSessionHasErrors('unit_id');
    $errors = session('errors')->get('unit_id');
    expect($errors[0])->toContain('sudah memiliki riwayat mutasi stok');
    expect($product->fresh()->unit_id)->toBe($this->unitPcs->id);
});

test('TC-AUTHZ-04: Staff Gudang (SG) receives NO price data in HTML or JSON (AC-PRD-06, BR-26)', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'purchase_price' => 777000.00,
        'selling_price' => 888000.00,
    ]);

    // 1. Staff Gudang visits products index
    $responseIndexSG = $this->actingAs($this->staffGudang)->get('/products');
    $responseIndexSG->assertOk();
    $responseIndexSG->assertDontSee('Harga Beli');
    $responseIndexSG->assertDontSee('Harga Jual');
    $responseIndexSG->assertDontSee('777.000');
    $responseIndexSG->assertDontSee('888.000');

    // 2. Staff Gudang visits product detail
    $responseShowSG = $this->actingAs($this->staffGudang)->get("/products/{$product->id}");
    $responseShowSG->assertOk();
    $responseShowSG->assertDontSee('Estimasi Harga Beli');
    $responseShowSG->assertDontSee('Estimasi Harga Jual');
    $responseShowSG->assertDontSee('777.000');
    $responseShowSG->assertDontSee('888.000');

    // 3. Super Admin visits same page -> prices ARE visible
    $responseShowSA = $this->actingAs($this->superAdmin)->get("/products/{$product->id}");
    $responseShowSA->assertOk();
    $responseShowSA->assertSee('Estimasi Harga Beli');
    $responseShowSA->assertSee('777.000');
});

test('TC-SEC-05: fake image, SVG, oversized file, and invalid dimensions are rejected (AC-PRD-07, SEC-08)', function () {
    Storage::fake('public');

    // 1. Fake image: file with .jpg extension containing plain text
    $fakeImage = UploadedFile::fake()->createWithContent('malicious.jpg', '<?php echo "evil script"; ?>');
    $responseFake = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Barang Fake Image',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 0,
        'photo' => $fakeImage,
    ]);
    $responseFake->assertSessionHasErrors('photo');

    // 2. SVG file (SEC-08: SVG strictly rejected)
    $svgFile = UploadedFile::fake()->createWithContent('icon.svg', '<svg xmlns="http://www.w3.org/2000/svg"><circle r="10"/></svg>');
    $responseSvg = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Barang SVG',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 0,
        'photo' => $svgFile,
    ]);
    $responseSvg->assertSessionHasErrors('photo');

    // 3. Oversized file (> 2 MB = 2048 KB)
    $oversized = UploadedFile::fake()->image('big.jpg', 500, 500)->size(2500);
    $responseOversized = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Barang Big Photo',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 0,
        'photo' => $oversized,
    ]);
    $responseOversized->assertSessionHasErrors('photo');

    // 4. Invalid dimension (< 100x100 px)
    $tooSmall = UploadedFile::fake()->image('tiny.jpg', 50, 50);
    $responseSmall = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Barang Small Photo',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 0,
        'photo' => $tooSmall,
    ]);
    $responseSmall->assertSessionHasErrors('photo');

    // 5. Valid image (200x200 px, real png)
    $validImage = UploadedFile::fake()->image('product.png', 200, 200)->size(500);
    $responseValid = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Barang Foto Valid',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 0,
        'photo' => $validImage,
    ]);
    $responseValid->assertRedirect('/products');

    $product = Product::where('name', 'Barang Foto Valid')->firstOrFail();
    expect($product->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($product->photo_path);
});

test('TC-PRD-06: negative prices, min_stock, or opening_stock are rejected (AC-PRD-08, BR-02)', function () {
    $response = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Barang Nilai Negatif',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'purchase_price' => -1000,
        'selling_price' => -500,
        'min_stock' => -1,
        'opening_stock' => -10,
    ]);

    $response->assertSessionHasErrors(['purchase_price', 'selling_price', 'min_stock', 'opening_stock']);
});

test('TC-PRD-07: current_stock in request is completely ignored and arch check confirms StockService is sole writer (AC-PRD-09, BR-06)', function () {
    // 1. Sending current_stock in create request:
    $responseCreate = $this->actingAs($this->adminGudang)->post('/products', [
        'name' => 'Barang Ignored Stock',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 0,
        'opening_stock' => 0,
        'current_stock' => 9999, // Injected parameter
    ]);
    $responseCreate->assertRedirect('/products');

    $product = Product::where('name', 'Barang Ignored Stock')->firstOrFail();
    expect((float) $product->current_stock)->toBe(0.0);

    // 2. Sending current_stock in update request:
    $responseUpdate = $this->actingAs($this->adminGudang)->put("/products/{$product->id}", [
        'name' => 'Barang Ignored Stock Updated',
        'category_id' => $this->category->id,
        'unit_id' => $this->unitPcs->id,
        'min_stock' => 0,
        'current_stock' => 8888, // Injected parameter
    ]);
    $responseUpdate->assertRedirect('/products');
    expect((float) $product->fresh()->current_stock)->toBe(0.0);

    // 3. Architectural check: verify Product model fillable does NOT contain current_stock
    $productModel = new Product;
    expect(in_array('current_stock', $productModel->getFillable(), true))->toBeFalse();
});
