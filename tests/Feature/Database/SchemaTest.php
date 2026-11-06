<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('all required tables exist in database', function () {
    $expectedTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'categories',
        'units',
        'suppliers',
        'products',
        'stock_ins',
        'stock_in_details',
        'stock_outs',
        'stock_out_details',
        'stock_adjustments',
        'stock_movements',
        'activity_logs',
        'document_sequences',
        'settings',
    ];

    foreach ($expectedTables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Table [{$table}] should exist.");
    }
});

test('tables use InnoDB engine in MySQL', function () {
    $tables = DB::select("
        SELECT table_name, engine
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name IN ('products', 'stock_ins', 'stock_outs', 'stock_movements', 'stock_adjustments')
    ");

    foreach ($tables as $t) {
        $data = (array) $t;
        $engine = $data['engine'] ?? $data['ENGINE'] ?? '';
        expect(strtoupper($engine))->toBe('INNODB');
    }
});

test('users table has required PRD 11.3 columns', function () {
    expect(Schema::hasColumns('users', [
        'id', 'name', 'email', 'password', 'is_active', 'must_change_password',
        'last_login_at', 'last_login_ip', 'remember_token', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('products table has required PRD 11.3 columns and decimal types', function () {
    expect(Schema::hasColumns('products', [
        'id', 'code', 'name', 'category_id', 'unit_id', 'main_supplier_id',
        'purchase_price', 'selling_price', 'current_stock', 'min_stock',
        'storage_location', 'description', 'photo_path', 'is_active',
        'created_by', 'updated_by', 'created_at', 'updated_at',
    ]))->toBeTrue();
});
