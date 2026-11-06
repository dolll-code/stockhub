<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create();
    $this->unit = Unit::factory()->create();
    $this->supplier = Supplier::factory()->create();
    $this->user = User::factory()->create();
});

test('products table rejects negative purchase_price via CHECK constraint', function () {
    expect(function () {
        DB::table('products')->insert([
            'code' => 'BRG-CHECK-1',
            'name' => 'Barang Negatif',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => -100.00,
            'current_stock' => 0,
            'min_stock' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('products table rejects negative current_stock via CHECK constraint', function () {
    expect(function () {
        DB::table('products')->insert([
            'code' => 'BRG-CHECK-2',
            'name' => 'Stok Negatif',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 1000.00,
            'current_stock' => -5.000,
            'min_stock' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('products table rejects negative min_stock via CHECK constraint', function () {
    expect(function () {
        DB::table('products')->insert([
            'code' => 'BRG-CHECK-3',
            'name' => 'Min Stok Negatif',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 1000.00,
            'current_stock' => 0,
            'min_stock' => -1.000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('stock_in_details rejects non-positive quantity via CHECK constraint', function () {
    $stockInId = DB::table('stock_ins')->insertGetId([
        'number' => 'IN-202611-0001',
        'transaction_date' => now()->toDateString(),
        'source_type' => 'internal',
        'status' => 'draft',
        'total_amount' => 0,
        'created_by' => $this->user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $product = Product::factory()->create();

    expect(function () use ($stockInId, $product) {
        DB::table('stock_in_details')->insert([
            'stock_in_id' => $stockInId,
            'product_id' => $product->id,
            'quantity' => 0.000,
            'unit_price' => 1000.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('stock_ins rejects purchase source_type without supplier via CHECK constraint', function () {
    expect(function () {
        DB::table('stock_ins')->insert([
            'number' => 'IN-202611-0002',
            'transaction_date' => now()->toDateString(),
            'source_type' => 'purchase',
            'supplier_id' => null, // Wajib bila purchase
            'status' => 'draft',
            'total_amount' => 0,
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('stock_adjustments rejects difference equal to 0 via CHECK constraint', function () {
    $product = Product::factory()->create();

    expect(function () use ($product) {
        DB::table('stock_adjustments')->insert([
            'number' => 'ADJ-202611-0001',
            'adjustment_date' => now()->toDateString(),
            'product_id' => $product->id,
            'system_qty' => 10.000,
            'physical_qty' => 10.000,
            'difference' => 0.000,
            'reason_code' => 'count_error',
            'notes' => 'Tidak ada selisih',
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('stock_adjustments rejects difference mismatch formula via CHECK constraint', function () {
    $product = Product::factory()->create();

    expect(function () use ($product) {
        DB::table('stock_adjustments')->insert([
            'number' => 'ADJ-202611-0002',
            'adjustment_date' => now()->toDateString(),
            'product_id' => $product->id,
            'system_qty' => 10.000,
            'physical_qty' => 8.000,
            'difference' => 5.000, // Harus -2.000 (8 - 10)
            'reason_code' => 'damaged',
            'notes' => 'Formula selisih salah',
            'created_by' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('stock_movements rejects formula mismatch via CHECK constraint', function () {
    $product = Product::factory()->create();

    expect(function () use ($product) {
        DB::table('stock_movements')->insert([
            'product_id' => $product->id,
            'movement_type' => 'stock_in',
            'movement_date' => now()->toDateString(),
            'qty_before' => 10.000,
            'qty_change' => 5.000,
            'qty_after' => 20.000, // Salah! Harus 15.000 (10 + 5)
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});

test('stock_movements rejects qty_change equal to 0 via CHECK constraint', function () {
    $product = Product::factory()->create();

    expect(function () use ($product) {
        DB::table('stock_movements')->insert([
            'product_id' => $product->id,
            'movement_type' => 'stock_in',
            'movement_date' => now()->toDateString(),
            'qty_before' => 10.000,
            'qty_change' => 0.000,
            'qty_after' => 10.000,
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);
    })->toThrow(QueryException::class);
});
