<?php

use App\Enums\StockInSource;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('only Super Admin can access and update Settings; AG, SG, MG receive 403 (§4, FR-SET-01)', function () {
    // 1. Super Admin is allowed
    $this->actingAs($this->superAdmin)->get('/settings')->assertOk();
    $this->actingAs($this->superAdmin)->put('/settings', [
        'company_name' => 'PT StockHub Jaya',
        'max_backdate_days' => 7,
    ])->assertRedirect('/settings');

    // 2. Admin Gudang is forbidden (403)
    $this->actingAs($this->adminGudang)->get('/settings')->assertForbidden();
    $this->actingAs($this->adminGudang)->put('/settings', ['company_name' => 'Illegal'])->assertForbidden();

    // 3. Staff Gudang is forbidden (403)
    $this->actingAs($this->staffGudang)->get('/settings')->assertForbidden();
    $this->actingAs($this->staffGudang)->put('/settings', ['company_name' => 'Illegal'])->assertForbidden();

    // 4. Manajer is forbidden (403)
    $this->actingAs($this->manajer)->get('/settings')->assertForbidden();
    $this->actingAs($this->manajer)->put('/settings', ['company_name' => 'Illegal'])->assertForbidden();
});

test('TC-SYS-02: changing max_backdate_days to 3 rejects transaction dated 4 days ago (AC-SYS-02, FR-SET-01, BR-20)', function () {
    // Set max_backdate_days to 3
    Setting::setValue('max_backdate_days', '3');

    $category = Category::create(['name' => 'Elektronik Backdate', 'is_active' => true]);
    $unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);
    $supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'PT Mitra', 'is_active' => true]);
    $product = Product::factory()->create(['category_id' => $category->id, 'unit_id' => $unit->id]);

    // Transaction dated 4 days ago -> must be rejected
    $fourDaysAgo = now()->subDays(4)->toDateString();

    $response = $this->actingAs($this->adminGudang)->post('/stock-ins', [
        'transaction_date' => $fourDaysAgo,
        'source_type' => StockInSource::INTERNAL->value,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 10],
        ],
    ]);

    $response->assertSessionHasErrors('transaction_date');
    $errors = session('errors')->all();
    expect($errors[0])->toContain('maksimal 3 hari yang lalu');

    // Transaction dated 2 days ago -> must be accepted
    $twoDaysAgo = now()->subDays(2)->toDateString();
    $responseValid = $this->actingAs($this->adminGudang)->post('/stock-ins', [
        'transaction_date' => $twoDaysAgo,
        'source_type' => StockInSource::INTERNAL->value,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 10],
        ],
    ]);
    $responseValid->assertSessionHasNoErrors();
});

test('settings update supports logo upload, removal, and records ActivityLogger audit entry (FR-SET-02)', function () {
    Storage::fake('public');

    $fakeLogo = UploadedFile::fake()->image('logo.png', 200, 100)->size(500); // 500 KB (< 1 MB)

    $response = $this->actingAs($this->superAdmin)->put('/settings', [
        'company_name' => 'PT Gudang Berkah Sejahtera',
        'company_address' => 'Jl. Kebon Jeruk No. 88, Jakarta Barat',
        'company_phone' => '021-5551234',
        'max_backdate_days' => 14,
        'company_logo' => $fakeLogo,
    ]);

    $response->assertRedirect('/settings');

    expect(Setting::getValue('company_name'))->toBe('PT Gudang Berkah Sejahtera');
    expect(Setting::getValue('company_address'))->toBe('Jl. Kebon Jeruk No. 88, Jakarta Barat');
    expect(Setting::getValue('company_phone'))->toBe('021-5551234');
    expect((int) Setting::getValue('max_backdate_days'))->toBe(14);

    $logoPath = Setting::getValue('company_logo_path');
    expect($logoPath)->not->toBeNull();
    Storage::disk('public')->assertExists($logoPath);

    // Verify audit log
    $log = ActivityLog::where('action', 'setting.updated')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($this->superAdmin->id);
    expect($log->new_values['company_name'])->toBe('PT Gudang Berkah Sejahtera');

    // Test logo removal
    $responseRemove = $this->actingAs($this->superAdmin)->put('/settings', [
        'company_name' => 'PT Gudang Berkah Sejahtera',
        'max_backdate_days' => 14,
        'remove_logo' => true,
    ]);
    $responseRemove->assertRedirect('/settings');
    expect(Setting::getValue('company_logo_path'))->toBeNull();
});
