<?php

use App\Enums\MovementType;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SettingSeeder::class);

    $this->user = User::factory()->create();

    $this->category = Category::create(['name' => 'Elektronik Reconcile', 'is_active' => true]);
    $this->unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);

    $this->product = Product::factory()->create([
        'code' => 'BRG-REC-01',
        'name' => 'Produk Reconcile',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'current_stock' => 20,
        'is_active' => true,
    ]);

    StockMovement::create([
        'product_id' => $this->product->id,
        'movement_type' => MovementType::OPENING_BALANCE,
        'movement_date' => now()->toDateString(),
        'qty_before' => 0,
        'qty_change' => 20,
        'qty_after' => 20,
        'user_id' => $this->user->id,
    ]);
});

test('TC-SYS-01: reconcile detects discrepancy when current_stock is modified manual without altering database data (AC-SYS-01, FR-SYS-01, BR-28)', function () {
    // 1. When data is consistent: reconcile returns SUCCESS (0)
    $exitCodeConsistent = Artisan::call('stockhub:reconcile');
    expect($exitCodeConsistent)->toBe(0);

    $logConsistent = ActivityLog::where('action', 'system.reconcile')->latest('id')->first();
    expect($logConsistent)->not->toBeNull();
    expect($logConsistent->new_values['discrepancies_count'])->toBe(0);

    // 2. Simulate manual tampering: modify current_stock directly in DB from 20 to 25
    $this->product->forceFill(['current_stock' => 25.0])->save();

    $exitCodeTampered = Artisan::call('stockhub:reconcile');
    expect($exitCodeTampered)->toBe(1); // Command::FAILURE when discrepancy exists

    // Verify discrepancy reported in ActivityLogger
    $logTampered = ActivityLog::where('action', 'system.reconcile')->latest('id')->first();
    expect($logTampered)->not->toBeNull();
    expect($logTampered->new_values['discrepancies_count'])->toBe(1);
    expect($logTampered->new_values['discrepancies'][0]['code'])->toBe('BRG-REC-01');
    expect((float) $logTampered->new_values['discrepancies'][0]['difference'])->toBe(5.0);

    // BR-28: DATA IS NEVER MODIFIED AUTOMATICALLY!
    expect((float) $this->product->fresh()->current_stock)->toBe(25.0);
    expect(StockMovement::where('product_id', $this->product->id)->count())->toBe(1);
});

test('reconcile detects broken movement chain continuity', function () {
    // Insert second movement with disconnected qty_before (starts at 30 instead of 20)
    StockMovement::create([
        'product_id' => $this->product->id,
        'movement_type' => MovementType::STOCK_IN,
        'movement_date' => now()->toDateString(),
        'qty_before' => 30, // Discontinuity! previous was 20
        'qty_change' => 10,
        'qty_after' => 40,
        'user_id' => $this->user->id,
    ]);

    $this->product->forceFill(['current_stock' => 30.0])->save();

    Artisan::call('stockhub:reconcile');

    $log = ActivityLog::where('action', 'system.reconcile')->latest('id')->first();
    expect($log->new_values['discrepancies_count'])->toBeGreaterThanOrEqual(1);
    expect($log->new_values['discrepancies'][0]['chain_broken'])->toBeTrue();
});

test('TC-SYS-03: timezone is Asia/Jakarta and transactions near midnight (23:30 WIB) record same business date (AC-SYS-03, BR-32)', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta');

    // Create a time at 23:30 WIB
    $nearMidnight = Carbon::create(2026, 11, 8, 23, 30, 0, 'Asia/Jakarta');
    Carbon::setTestNow($nearMidnight);

    $movement = StockMovement::create([
        'product_id' => $this->product->id,
        'movement_type' => MovementType::STOCK_IN,
        'movement_date' => now()->toDateString(),
        'qty_before' => 20,
        'qty_change' => 5,
        'qty_after' => 25,
        'user_id' => $this->user->id,
    ]);

    expect($movement->movement_date->toDateString())->toBe('2026-11-08');
    expect($movement->created_at->timezone('Asia/Jakarta')->toDateString())->toBe('2026-11-08');
    expect($movement->created_at->timezone('Asia/Jakarta')->hour)->toBe(23);
    expect($movement->created_at->timezone('Asia/Jakarta')->minute)->toBe(30);

    Carbon::setTestNow(); // Reset
});

test('scheduler registers stockhub:reconcile daily at 01:00 with Asia/Jakarta timezone (FR-SYS-01, PRD §5.14)', function () {
    $schedule = app(Schedule::class);

    $events = collect($schedule->events())->filter(function ($event) {
        return str_contains($event->command, 'stockhub:reconcile');
    });

    expect($events)->not->toBeEmpty();
    $reconcileEvent = $events->first();

    expect($reconcileEvent->expression)->toBe('0 1 * * *'); // 01:00 daily
    expect($reconcileEvent->timezone)->toBe('Asia/Jakarta');
});
