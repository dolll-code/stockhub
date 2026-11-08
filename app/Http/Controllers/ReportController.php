<?php

namespace App\Http\Controllers;

use App\Enums\AdjustmentReason;
use App\Enums\MovementType;
use App\Enums\StockInSource;
use App\Enums\StockOutPurpose;
use App\Exceptions\ExportLimitExceededException;
use App\Exports\StockAdjustmentReportExport;
use App\Exports\StockInReportExport;
use App\Exports\StockMovementReportExport;
use App\Exports\StockOutReportExport;
use App\Exports\StockReportExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Reports\StockAdjustmentReportQuery;
use App\Reports\StockInReportQuery;
use App\Reports\StockMovementReportQuery;
use App\Reports\StockOutReportQuery;
use App\Reports\StockReportQuery;
use App\Services\ActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * Redirect base /reports to /reports/stock.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('reports.show', ['type' => 'stock']);
    }

    /**
     * Display a specific report screen (FR-RPT-01..05).
     * Accessible by SA, AG, MG; Staff Gudang is denied 403.
     */
    public function show(string $type, Request $request): View
    {
        Gate::authorize('reports.view');

        $validTypes = ['stock', 'stock-ins', 'stock-outs', 'adjustments', 'movements'];
        if (! in_array($type, $validTypes, true)) {
            abort(404, 'Jenis laporan tidak ditemukan.');
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $categories = Category::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        $filters = $request->all();

        $reportData = match ($type) {
            'stock' => app(StockReportQuery::class)->setFilters($filters)->paginate($perPage),
            'stock-ins' => app(StockInReportQuery::class)->setFilters($filters)->paginate($perPage),
            'stock-outs' => app(StockOutReportQuery::class)->setFilters($filters)->paginate($perPage),
            'adjustments' => app(StockAdjustmentReportQuery::class)->setFilters($filters)->paginate($perPage),
            'movements' => app(StockMovementReportQuery::class)->setFilters($filters)->paginate($perPage),
        };

        return view('reports.show', [
            'type' => $type,
            'reportData' => $reportData,
            'categories' => $categories,
            'products' => $products,
            'suppliers' => $suppliers,
            'canViewPrice' => $canViewPrice,
            'sources' => StockInSource::cases(),
            'purposes' => StockOutPurpose::cases(),
            'reasons' => AdjustmentReason::cases(),
            'movementTypes' => MovementType::cases(),
        ]);
    }

    /**
     * Export report to PDF or Excel (.xlsx) (FR-EXP-01..04).
     *
     * @throws ExportLimitExceededException
     */
    public function export(string $type, string $format, Request $request, ActivityLogger $logger): Response
    {
        Gate::authorize('reports.export');

        $validTypes = ['stock', 'stock-ins', 'stock-outs', 'adjustments', 'movements'];
        if (! in_array($type, $validTypes, true)) {
            abort(404, 'Jenis laporan tidak ditemukan.');
        }

        $format = strtolower($format);
        if (! in_array($format, ['pdf', 'xlsx'], true)) {
            abort(404, 'Format ekspor tidak didukung.');
        }

        $filters = $request->all();
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        // Instantiate Single Source of Truth Query Object (BR-31)
        $queryObject = match ($type) {
            'stock' => app(StockReportQuery::class)->setFilters($filters),
            'stock-ins' => app(StockInReportQuery::class)->setFilters($filters),
            'stock-outs' => app(StockOutReportQuery::class)->setFilters($filters),
            'adjustments' => app(StockAdjustmentReportQuery::class)->setFilters($filters),
            'movements' => app(StockMovementReportQuery::class)->setFilters($filters),
        };

        // Enforce export limits (FR-EXP-03)
        $maxLimit = $format === 'pdf' ? 5000 : 100000;
        $totalRows = $queryObject->count();

        if ($totalRows > $maxLimit) {
            throw new ExportLimitExceededException($totalRows, $maxLimit);
        }

        // Filename conventions: stockhub-<laporan>-<YYYYMMDD-HHmm>.<ext> (FR-EXP-02)
        $slug = match ($type) {
            'stock' => 'persediaan',
            'stock-ins' => 'barang-masuk',
            'stock-outs' => 'barang-keluar',
            'adjustments' => 'penyesuaian',
            'movements' => 'pergerakan-stok',
        };
        $timestamp = now()->format('Ymd-Hi');
        $filename = "stockhub-{$slug}-{$timestamp}.{$format}";

        // Audit log entry before/after export (FR-EXP-04, AC-RPT-08)
        $logger->log(
            action: 'report.exported',
            description: "Pengguna [{$request->user()?->name}] mengekspor laporan [{$slug}] ({$format})",
            subject: null,
            actor: $request->user(),
            newValues: [
                'report_type' => $type,
                'format' => $format,
                'filters' => array_filter($filters, fn ($k) => $k !== 'page' && $k !== 'per_page', ARRAY_FILTER_USE_KEY),
                'row_count' => $totalRows,
            ],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        if ($format === 'xlsx') {
            $exportInstance = match ($type) {
                'stock' => new StockReportExport(
                    $queryObject->get(),
                    $canViewPrice,
                    ! empty($filters['as_of_date']) && Carbon::parse($filters['as_of_date'])->toDateString() < now()->toDateString()
                ),
                'stock-ins' => new StockInReportExport($queryObject->get(), $canViewPrice),
                'stock-outs' => new StockOutReportExport($queryObject->get()),
                'adjustments' => new StockAdjustmentReportExport($queryObject->get()),
                'movements' => new StockMovementReportExport($queryObject->get()),
            };

            return Excel::download($exportInstance, $filename);
        }

        // PDF Export via DomPDF (FR-EXP-01)
        $companyName = Setting::getValue('company_name', config('app.name', 'StockHub'));
        $companyAddress = Setting::getValue('company_address');
        $companyPhone = Setting::getValue('company_phone');
        $companyLogoPath = Setting::getValue('company_logo_path');
        $companyLogoBase64 = null;

        if ($companyLogoPath && Storage::disk('public')->exists($companyLogoPath)) {
            $logoContent = Storage::disk('public')->get($companyLogoPath);
            $mime = Storage::disk('public')->mimeType($companyLogoPath);
            $companyLogoBase64 = 'data:'.$mime.';base64,'.base64_encode($logoContent);
        }

        $activeFilters = $this->buildActiveFiltersString($type, $filters);
        $rows = $queryObject->get();

        $pdfView = "reports.pdf.{$type}";
        $isAsOf = ! empty($filters['as_of_date']) && Carbon::parse($filters['as_of_date'])->toDateString() < now()->toDateString();
        $asOfDate = $filters['as_of_date'] ?? null;

        $pdf = Pdf::loadView($pdfView, [
            'rows' => $rows,
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyPhone' => $companyPhone,
            'companyLogoBase64' => $companyLogoBase64,
            'activeFilters' => $activeFilters,
            'printedBy' => $request->user() ? $request->user()->name : 'Sistem',
            'canViewPrice' => $canViewPrice,
            'isAsOf' => $isAsOf,
            'asOfDate' => $asOfDate,
        ])->setPaper('a4', $type === 'stock' ? 'portrait' : 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Build human-readable active filters string for PDF header.
     *
     * @param  array<string, mixed>  $filters
     */
    protected function buildActiveFiltersString(string $type, array $filters): string
    {
        $parts = [];

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $from = ! empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->format('d/m/Y') : 'Awal';
            $to = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->format('d/m/Y') : 'Hari ini';
            $parts[] = "Periode: {$from} s.d. {$to}";
        }

        if (! empty($filters['as_of_date'])) {
            $parts[] = 'Per Tanggal: '.Carbon::parse($filters['as_of_date'])->format('d/m/Y');
        }

        if (! empty($filters['category_id'])) {
            $cat = Category::find($filters['category_id']);
            if ($cat) {
                $parts[] = "Kategori: {$cat->name}";
            }
        }

        if (! empty($filters['product_id'])) {
            $prod = Product::find($filters['product_id']);
            if ($prod) {
                $parts[] = "Barang: [{$prod->code}] {$prod->name}";
            }
        }

        if (! empty($filters['supplier_id'])) {
            $sup = Supplier::find($filters['supplier_id']);
            if ($sup) {
                $parts[] = "Supplier: {$sup->name}";
            }
        }

        if (! empty($filters['source_type'])) {
            $parts[] = "Sumber: {$filters['source_type']}";
        }

        if (! empty($filters['purpose_type'])) {
            $parts[] = "Peruntukan: {$filters['purpose_type']}";
        }

        if (! empty($filters['reason_code'])) {
            $parts[] = "Alasan: {$filters['reason_code']}";
        }

        if (! empty($filters['movement_type'])) {
            $parts[] = "Jenis Mutasi: {$filters['movement_type']}";
        }

        if (! empty($filters['stock_status'])) {
            $parts[] = "Status Stok: {$filters['stock_status']}";
        }

        if (! empty($filters['status'])) {
            $parts[] = 'Status Keaktifan: '.($filters['status'] === 'active' ? 'Aktif' : 'Nonaktif');
        }

        return ! empty($parts) ? implode(' | ', $parts) : 'Semua Data';
    }
}
