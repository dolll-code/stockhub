<?php

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('stock_movements model rejects updating via Eloquent guard (BR-15)', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'movement_type' => 'stock_in',
        'movement_date' => now()->toDateString(),
        'qty_before' => 0.000,
        'qty_change' => 10.000,
        'qty_after' => 10.000,
        'user_id' => $user->id,
        'notes' => 'Catatan awal',
    ]);

    expect(function () use ($movement) {
        $movement->update(['notes' => 'Catatan diubah']);
    })->toThrow(RuntimeException::class, 'StockMovement is append-only and cannot be updated (BR-15).');
});

test('stock_movements model rejects deleting via Eloquent guard (BR-15)', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $movement = StockMovement::create([
        'product_id' => $product->id,
        'movement_type' => 'stock_in',
        'movement_date' => now()->toDateString(),
        'qty_before' => 0.000,
        'qty_change' => 10.000,
        'qty_after' => 10.000,
        'user_id' => $user->id,
    ]);

    expect(function () use ($movement) {
        $movement->delete();
    })->toThrow(RuntimeException::class, 'StockMovement is append-only and cannot be deleted (BR-15).');
});

test('activity_logs model rejects updating via Eloquent guard (BR-15)', function () {
    $user = User::factory()->create();

    $log = ActivityLog::create([
        'user_id' => $user->id,
        'action' => 'auth.login',
        'description' => 'Login awal',
        'ip_address' => '127.0.0.1',
    ]);

    expect(function () use ($log) {
        $log->update(['description' => 'Deskripsi diedit']);
    })->toThrow(RuntimeException::class, 'ActivityLog is append-only and cannot be updated (BR-15).');
});

test('activity_logs model rejects deleting via Eloquent guard (BR-15)', function () {
    $user = User::factory()->create();

    $log = ActivityLog::create([
        'user_id' => $user->id,
        'action' => 'auth.login',
        'description' => 'Login awal',
        'ip_address' => '127.0.0.1',
    ]);

    expect(function () use ($log) {
        $log->delete();
    })->toThrow(RuntimeException::class, 'ActivityLog is append-only and cannot be deleted (BR-15).');
});
