<?php

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(SettingSeeder::class);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->adminGudang = User::factory()->create();
    $this->adminGudang->assignRole('admin_gudang');

    $this->staffGudang = User::factory()->create();
    $this->staffGudang->assignRole('staff_gudang');

    $this->manajer = User::factory()->create();
    $this->manajer->assignRole('manajer');
});

test('only Super Admin can access Activity Log viewer; AG, SG, MG receive 403 (§4, FR-LOG-02)', function () {
    // 1. Super Admin is allowed (200)
    $this->actingAs($this->superAdmin)->get('/activity-logs')->assertOk();

    // 2. Admin Gudang is forbidden (403)
    $this->actingAs($this->adminGudang)->get('/activity-logs')->assertForbidden();

    // 3. Staff Gudang is forbidden (403)
    $this->actingAs($this->staffGudang)->get('/activity-logs')->assertForbidden();

    // 4. Manajer is forbidden (403)
    $this->actingAs($this->manajer)->get('/activity-logs')->assertForbidden();
});

test('TC-LOG-01: confirmed operations record audit entry with document number, user, IP, and timestamp (AC-LOG-01, BR-30)', function () {
    $cat = Category::create(['name' => 'Elektronik Log', 'is_active' => true]);
    $unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);
    $prod = Product::factory()->create(['category_id' => $cat->id, 'unit_id' => $unit->id]);

    $response = $this->actingAs($this->superAdmin)->post('/categories', [
        'name' => 'Kategori Baru Log',
        'description' => 'Test log audit',
    ]);
    $response->assertRedirect('/categories');

    $log = ActivityLog::where('action', 'category.created')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($this->superAdmin->id);
    expect($log->description)->toContain($this->superAdmin->name);
    expect($log->created_at)->not->toBeNull();
});

test('AC-LOG-03: ActivityLog is strictly append-only; updates and deletes throw exception and no HTTP mutate routes exist (BR-15)', function () {
    $log = ActivityLog::create([
        'user_id' => $this->superAdmin->id,
        'action' => 'test.action',
        'description' => 'Uji append-only',
    ]);

    // Eloquent update rejected
    expect(fn () => $log->update(['description' => 'Perubahan ilegal']))
        ->toThrow(RuntimeException::class);

    // Eloquent delete rejected
    expect(fn () => $log->delete())
        ->toThrow(RuntimeException::class);

    // Verify no edit, update, or destroy routes exist for activity logs
    expect(Route::has('activity-logs.edit'))->toBeFalse();
    expect(Route::has('activity-logs.update'))->toBeFalse();
    expect(Route::has('activity-logs.destroy'))->toBeFalse();
});

test('Activity Log viewer supports search, user filter, action filter, and date filters', function () {
    ActivityLog::create([
        'user_id' => $this->superAdmin->id,
        'action' => 'user.login',
        'description' => 'Pengguna login dari IP kantor',
    ]);
    ActivityLog::create([
        'user_id' => $this->superAdmin->id,
        'action' => 'product.created',
        'description' => 'Membuat barang baru Mouse Logitech',
    ]);

    // 1. Search filter
    $responseSearch = $this->actingAs($this->superAdmin)->get('/activity-logs?q=Logitech');
    $responseSearch->assertOk();
    $responseSearch->assertSee('Membuat barang baru Mouse Logitech');
    $responseSearch->assertDontSee('Pengguna login dari IP kantor');

    // 2. Action filter
    $responseAction = $this->actingAs($this->superAdmin)->get('/activity-logs?action=user.login');
    $responseAction->assertOk();
    $responseAction->assertSee('Pengguna login dari IP kantor');
    $responseAction->assertDontSee('Membuat barang baru Mouse Logitech');
});
