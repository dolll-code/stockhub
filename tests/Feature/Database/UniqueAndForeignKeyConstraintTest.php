<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockInDetail;
use App\Models\StockOut;
use App\Models\StockOutDetail;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('category name must be unique', function () {
    Category::factory()->create(['name' => 'Elektronik']);

    expect(function () {
        Category::factory()->create(['name' => 'Elektronik']);
    })->toThrow(QueryException::class);
});

test('unit name and symbol must be unique', function () {
    Unit::factory()->create(['name' => 'Pieces', 'symbol' => 'pcs']);

    expect(function () {
        Unit::factory()->create(['name' => 'Pieces', 'symbol' => 'pc2']);
    })->toThrow(QueryException::class);

    expect(function () {
        Unit::factory()->create(['name' => 'Piece Duplicate', 'symbol' => 'pcs']);
    })->toThrow(QueryException::class);
});

test('supplier code must be unique', function () {
    Supplier::factory()->create(['code' => 'SUP-0001']);

    expect(function () {
        Supplier::factory()->create(['code' => 'SUP-0001']);
    })->toThrow(QueryException::class);
});

test('product code must be unique', function () {
    Product::factory()->create(['code' => 'BRG-0001']);

    expect(function () {
        Product::factory()->create(['code' => 'BRG-0001']);
    })->toThrow(QueryException::class);
});

test('stock_in_details rejects duplicate product in single document (BR-11)', function () {
    $stockIn = StockIn::factory()->create();
    $product = Product::factory()->create();

    StockInDetail::factory()->create([
        'stock_in_id' => $stockIn->id,
        'product_id' => $product->id,
    ]);

    expect(function () use ($stockIn, $product) {
        StockInDetail::factory()->create([
            'stock_in_id' => $stockIn->id,
            'product_id' => $product->id,
        ]);
    })->toThrow(QueryException::class);
});

test('stock_out_details rejects duplicate product in single document (BR-11)', function () {
    $stockOut = StockOut::factory()->create();
    $product = Product::factory()->create();

    StockOutDetail::factory()->create([
        'stock_out_id' => $stockOut->id,
        'product_id' => $product->id,
    ]);

    expect(function () use ($stockOut, $product) {
        StockOutDetail::factory()->create([
            'stock_out_id' => $stockOut->id,
            'product_id' => $product->id,
        ]);
    })->toThrow(QueryException::class);
});

test('foreign key restrict prevents deleting category with products (BR-03)', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    expect(function () use ($category) {
        $category->delete();
    })->toThrow(QueryException::class);
});

test('foreign key restrict prevents deleting unit with products (BR-03)', function () {
    $unit = Unit::factory()->create();
    Product::factory()->create(['unit_id' => $unit->id]);

    expect(function () use ($unit) {
        $unit->delete();
    })->toThrow(QueryException::class);
});

test('foreign key restrict prevents deleting supplier with products (BR-03)', function () {
    $supplier = Supplier::factory()->create();
    Product::factory()->create(['main_supplier_id' => $supplier->id]);

    expect(function () use ($supplier) {
        $supplier->delete();
    })->toThrow(QueryException::class);
});

test('foreign key restrict prevents deleting product with stock_in_details', function () {
    $detail = StockInDetail::factory()->create();

    expect(function () use ($detail) {
        $detail->product->delete();
    })->toThrow(QueryException::class);
});
