<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->manajer = User::factory()->create();
    $this->manajer->assignRole('manajer');

    $this->category = Category::create(['name' => 'Elektronik', 'is_active' => true]);
    $this->unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);
    $this->supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'PT Mitra', 'is_active' => true]);

    $this->product = Product::factory()->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
    ]);
});

test('TC-AUTHZ-03: Manajer (MG) cannot create or update any data (all POST/PUT/PATCH return 403) (§4, TC-AUTHZ-03)', function () {
    // 1. Categories
    $this->actingAs($this->manajer)->post('/categories', ['name' => 'Illegal'])->assertForbidden();
    $this->actingAs($this->manajer)->put("/categories/{$this->category->id}", ['name' => 'Illegal'])->assertForbidden();
    $this->actingAs($this->manajer)->patch("/categories/{$this->category->id}/status")->assertForbidden();

    // 2. Units
    $this->actingAs($this->manajer)->post('/units', ['name' => 'Illegal', 'symbol' => 'ill'])->assertForbidden();
    $this->actingAs($this->manajer)->put("/units/{$this->unit->id}", ['name' => 'Illegal', 'symbol' => 'ill'])->assertForbidden();
    $this->actingAs($this->manajer)->patch("/units/{$this->unit->id}/status")->assertForbidden();

    // 3. Suppliers
    $this->actingAs($this->manajer)->post('/suppliers', ['name' => 'Illegal'])->assertForbidden();
    $this->actingAs($this->manajer)->put("/suppliers/{$this->supplier->id}", ['name' => 'Illegal'])->assertForbidden();
    $this->actingAs($this->manajer)->patch("/suppliers/{$this->supplier->id}/status")->assertForbidden();

    // 4. Products
    $this->actingAs($this->manajer)->post('/products', ['name' => 'Illegal'])->assertForbidden();
    $this->actingAs($this->manajer)->put("/products/{$this->product->id}", ['name' => 'Illegal'])->assertForbidden();
    $this->actingAs($this->manajer)->patch("/products/{$this->product->id}/status")->assertForbidden();

    // 5. Stock-ins
    $stockIn = StockIn::create([
        'number' => 'IN-202611-0001',
        'transaction_date' => now()->toDateString(),
        'source_type' => 'internal',
        'status' => 'draft',
        'created_by' => $this->manajer->id,
    ]);
    $this->actingAs($this->manajer)->post('/stock-ins', [])->assertForbidden();
    $this->actingAs($this->manajer)->put("/stock-ins/{$stockIn->id}", [])->assertForbidden();
    $this->actingAs($this->manajer)->post("/stock-ins/{$stockIn->id}/confirm")->assertForbidden();
    $this->actingAs($this->manajer)->post("/stock-ins/{$stockIn->id}/cancel", ['cancel_reason' => 'Alasan pembatalan'])->assertForbidden();

    // 6. Stock-outs
    $stockOut = StockOut::create([
        'number' => 'OUT-202611-0001',
        'transaction_date' => now()->toDateString(),
        'purpose_type' => 'usage',
        'destination' => 'Tujuan',
        'status' => 'draft',
        'created_by' => $this->manajer->id,
    ]);
    $this->actingAs($this->manajer)->post('/stock-outs', [])->assertForbidden();
    $this->actingAs($this->manajer)->put("/stock-outs/{$stockOut->id}", [])->assertForbidden();
    $this->actingAs($this->manajer)->post("/stock-outs/{$stockOut->id}/confirm")->assertForbidden();
    $this->actingAs($this->manajer)->post("/stock-outs/{$stockOut->id}/cancel", ['cancel_reason' => 'Alasan pembatalan'])->assertForbidden();

    // 7. Adjustments
    $this->actingAs($this->manajer)->post('/adjustments', [])->assertForbidden();
});
