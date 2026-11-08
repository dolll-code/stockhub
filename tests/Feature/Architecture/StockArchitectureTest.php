<?php

use App\Enums\MovementType;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Support\Facades\Route;

test('architecture: products.current_stock is NOT fillable in Product model (BR-06)', function () {
    $product = new Product;
    expect($product->isFillable('current_stock'))->toBeFalse();
});

test('architecture: only StockService writes products.current_stock across entire codebase (BR-06)', function () {
    $appDir = app_path();
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir));

    $disallowedFilesWritingStock = [];

    foreach ($files as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }

        $filePath = $file->getRealPath();
        $content = file_get_contents($filePath);

        // Disallow direct forceFill/update on current_stock anywhere except StockService
        if (str_contains($content, "'current_stock'") || str_contains($content, '"current_stock"')) {
            if (
                preg_match("/(update|forceFill)\s*\(\s*\[[^\]]*['\"]current_stock['\"]/i", $content) ||
                preg_match('/->current_stock\s*=/i', $content)
            ) {
                $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $filePath);
                // The only allowed file is StockService.php
                if (! str_contains($relative, 'StockService.php')) {
                    $disallowedFilesWritingStock[] = $relative;
                }
            }
        }
    }

    expect($disallowedFilesWritingStock)->toBeEmpty(
        'Found files outside StockService attempting to write current_stock: '.implode(', ', $disallowedFilesWritingStock)
    );
});

test('architecture: no DELETE HTTP routes exist for business master and transaction entities (BR-03, BR-12)', function () {
    $routes = Route::getRoutes();
    $disallowedBusinessDeletePaths = [];

    $businessPrefixes = [
        'users',
        'profile',
        'categories',
        'units',
        'suppliers',
        'stock-ins',
        'stock-outs',
        'adjustments',
        'stock-movements',
    ];

    foreach ($routes as $route) {
        if (in_array('DELETE', $route->methods(), true)) {
            $uri = $route->uri();
            foreach ($businessPrefixes as $prefix) {
                // Ignore photo deletion which is allowed
                if ($uri === 'products/{product}/photo') {
                    continue;
                }
                if ($uri === $prefix || str_starts_with($uri, $prefix.'/')) {
                    $disallowedBusinessDeletePaths[] = $uri;
                }
            }
        }
    }

    expect($disallowedBusinessDeletePaths)->toBeEmpty(
        'Disallowed DELETE routes found for business entities: '.implode(', ', $disallowedBusinessDeletePaths)
    );
});

test('architecture: StockMovement and ActivityLog reject updates and deletes (BR-15)', function () {
    $user = User::factory()->create();
    $cat = Category::create(['name' => 'ArchCat', 'is_active' => true]);
    $unit = Unit::create(['name' => 'ArchUnit', 'symbol' => 'au', 'allow_decimal' => false, 'is_active' => true]);
    $prod = Product::factory()->create(['category_id' => $cat->id, 'unit_id' => $unit->id]);

    $movement = StockMovement::create([
        'product_id' => $prod->id,
        'movement_type' => MovementType::OPENING_BALANCE,
        'movement_date' => now()->toDateString(),
        'qty_before' => 0,
        'qty_change' => 10,
        'qty_after' => 10,
        'user_id' => $user->id,
    ]);

    expect(fn () => $movement->update(['notes' => 'illegal update']))->toThrow(RuntimeException::class);
    expect(fn () => $movement->delete())->toThrow(RuntimeException::class);

    $log = ActivityLog::create([
        'user_id' => $user->id,
        'action' => 'arch.test',
        'description' => 'Arch test log',
    ]);

    expect(fn () => $log->update(['description' => 'illegal update']))->toThrow(RuntimeException::class);
    expect(fn () => $log->delete())->toThrow(RuntimeException::class);
});
