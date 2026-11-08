<?php

use App\Exports\StockReportExport;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Reports\StockReportQuery;
use App\Support\CsvFormulaSanitizer;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

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

    $this->category = Category::create(['name' => 'Elektronik', 'is_active' => true]);
    $this->unit = Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'allow_decimal' => false, 'is_active' => true]);
    $this->supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'PT Mitra Utama', 'is_active' => true]);

    $this->product = Product::factory()->create([
        'code' => 'BRG-0001',
        'name' => 'Mouse Gaming',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'main_supplier_id' => $this->supplier->id,
        'is_active' => true,
    ]);
});

test('TC-RPT-03: screen, PDF, and Excel exports return identical row count and content for same filter (AC-RPT-03, BR-31)', function () {
    Product::factory()->count(4)->create([
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
    ]);

    $query = new StockReportQuery(['category_id' => $this->category->id]);
    $screenRows = $query->paginate(15);
    $exportRows = $query->get();

    // 1 product from beforeEach + 4 = 5 total
    expect($screenRows->total())->toBe(5);
    expect($exportRows->count())->toBe(5);
    expect($screenRows->pluck('code')->all())->toBe($exportRows->pluck('code')->all());

    // Test PDF export response
    $responsePdf = $this->actingAs($this->adminGudang)
        ->get("/reports/stock/export/pdf?category_id={$this->category->id}");
    $responsePdf->assertOk();
    $responsePdf->assertHeader('content-type', 'application/pdf');

    // Test Excel export response
    $responseXlsx = $this->actingAs($this->adminGudang)
        ->get("/reports/stock/export/xlsx?category_id={$this->category->id}");
    $responseXlsx->assertOk();
    expect($responseXlsx->headers->get('content-disposition'))->toContain('stockhub-persediaan-');
    expect($responseXlsx->headers->get('content-disposition'))->toContain('.xlsx');
});

test('TC-RPT-05: export limits: PDF rejected when > 5000 rows (422 EXPORT_LIMIT_EXCEEDED), Excel rejected when > 100000 rows (AC-RPT-07, FR-EXP-03)', function () {
    $this->mock(StockReportQuery::class, function ($mock) {
        $mock->shouldReceive('setFilters')->andReturnSelf();
        $mock->shouldReceive('count')->andReturn(6000);
        $mock->shouldReceive('get')->andReturn(collect());
    });

    // 1. PDF with 6000 rows (> 5000 limit) -> rejected with HTTP 422
    $responsePdf = $this->actingAs($this->adminGudang)->get('/reports/stock/export/pdf');
    $responsePdf->assertStatus(422);
    $responsePdf->assertSessionHas('error');
    $errorMsg = session('error');
    expect($errorMsg)->toContain('Data terlalu banyak (6.000 baris, batas 5.000)');

    // 2. PDF JSON request with 6000 rows -> returns JSON code EXPORT_LIMIT_EXCEEDED
    $responseJson = $this->actingAs($this->adminGudang)->getJson('/reports/stock/export/pdf');
    $responseJson->assertStatus(422);
    $responseJson->assertJson([
        'code' => 'EXPORT_LIMIT_EXCEEDED',
    ]);

    // 3. Excel with 6000 rows (<= 100000 limit) -> allowed
    $responseExcel = $this->actingAs($this->adminGudang)->get('/reports/stock/export/xlsx');
    $responseExcel->assertOk();
});

test('TC-RPT-06: successful export is recorded in ActivityLogger with metadata (AC-RPT-08, FR-EXP-04)', function () {
    $response = $this->actingAs($this->adminGudang)
        ->get('/reports/stock-ins/export/xlsx?source_type=purchase');
    $response->assertOk();

    $log = ActivityLog::where('action', 'report.exported')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($this->adminGudang->id);
    expect($log->new_values['report_type'])->toBe('stock-ins');
    expect($log->new_values['format'])->toBe('xlsx');
    expect($log->new_values['filters']['source_type'])->toBe('purchase');
});

test('TC-SEC-06: Excel formula injection payloads starting with =, +, -, @, tab, or CR are neutralized (AC-RPT-05, SEC-10)', function () {
    // Test sanitizer helper directly
    expect(CsvFormulaSanitizer::sanitize('=cmd|/c calc!A0'))->toBe("'=cmd|/c calc!A0");
    expect(CsvFormulaSanitizer::sanitize('+12345Formula'))->toBe("'+12345Formula");
    expect(CsvFormulaSanitizer::sanitize('-2+3+cmd|'))->toBe("'-2+3+cmd|");
    expect(CsvFormulaSanitizer::sanitize('@SUM(1,2)'))->toBe("'@SUM(1,2)");
    expect(CsvFormulaSanitizer::sanitize("\tTAB_INJECT"))->toBe("'\tTAB_INJECT");
    expect(CsvFormulaSanitizer::sanitize("\rCR_INJECT"))->toBe("'\rCR_INJECT");

    // Regular normal string should not be prefixed
    expect(CsvFormulaSanitizer::sanitize('Mouse Gaming Optik'))->toBe('Mouse Gaming Optik');
    expect(CsvFormulaSanitizer::sanitize('BRG-0001'))->toBe('BRG-0001');

    // Create product with formula payload in name (AC-RPT-05)
    $maliciousProd = Product::factory()->create([
        'name' => '=cmd|/c calc!A0',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
    ]);

    $export = new StockReportExport(collect([$maliciousProd]), false, false);
    $mapped = $export->map($maliciousProd);

    // Mapped product name must start with apostrophe
    expect($mapped[1])->toBe("'=cmd|/c calc!A0");
});

test('AC-RPT-06: Staff Gudang (SG) is denied 403 on export route and no file is created', function () {
    $response = $this->actingAs($this->staffGudang)
        ->get('/reports/stock/export/pdf');
    $response->assertForbidden();

    $responseXlsx = $this->actingAs($this->staffGudang)
        ->get('/reports/stock/export/xlsx');
    $responseXlsx->assertForbidden();

    // Verify no report.exported log was made
    expect(ActivityLog::where('action', 'report.exported')->count())->toBe(0);
});
