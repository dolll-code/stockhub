<?php

namespace App\Http\Controllers;

use App\Enums\AdjustmentReason;
use App\Enums\MovementType;
use App\Enums\StockInSource;
use App\Enums\StockOutPurpose;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Reports\StockAdjustmentReportQuery;
use App\Reports\StockInReportQuery;
use App\Reports\StockMovementReportQuery;
use App\Reports\StockOutReportQuery;
use App\Reports\StockReportQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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
            'stock' => (new StockReportQuery($filters))->paginate($perPage),
            'stock-ins' => (new StockInReportQuery($filters))->paginate($perPage),
            'stock-outs' => (new StockOutReportQuery($filters))->paginate($perPage),
            'adjustments' => (new StockAdjustmentReportQuery($filters))->paginate($perPage),
            'movements' => (new StockMovementReportQuery($filters))->paginate($perPage),
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
}
