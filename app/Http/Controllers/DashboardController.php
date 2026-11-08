<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display role-tailored dashboard view (FR-DSH-01..05).
     */
    public function index(Request $request): View
    {
        Gate::authorize('dashboard.view');

        /** @var User $user */
        $user = $request->user();
        $isStaff = $user->hasRole('staff_gudang');

        // Critical products (habis first, then lowest stock/min ratio) (FR-DSH-04)
        $criticalProducts = Product::with(['unit', 'category'])
            ->active()
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderByRaw('CASE WHEN current_stock <= 0 THEN 0 ELSE 1 END ASC, CASE WHEN min_stock > 0 THEN current_stock / min_stock ELSE 0 END ASC, name ASC')
            ->limit(10)
            ->get();

        // 10 latest transactions (masuk + keluar, semua status) (FR-DSH-03)
        $recentTransactions = $this->getRecentTransactions($isStaff ? $user->id : null);

        // Common stock counters (Live from SQL, not cached per PRD §5.3)
        $availableStockProductsCount = Product::active()->where('current_stock', '>', 0)->count();
        $lowStockProductsCount = Product::active()->where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'min_stock')->count();
        $outOfStockProductsCount = Product::active()->where('current_stock', '<=', 0)->count();

        if ($isStaff) {
            // Staff Gudang view (FR-DSH-05: limited, no global counts, no supplier count, no price)
            return view('dashboard', [
                'isStaff' => true,
                'availableStockProductsCount' => $availableStockProductsCount,
                'lowStockProductsCount' => $lowStockProductsCount,
                'outOfStockProductsCount' => $outOfStockProductsCount,
                'criticalProducts' => $criticalProducts,
                'recentTransactions' => $recentTransactions,
            ]);
        }

        // SA / AG / MG management view (FR-DSH-01)
        $activeProductsCount = Product::active()->count();
        $activeCategoriesCount = Category::active()->count();
        $activeSuppliersCount = Supplier::active()->count();

        // Confirmed transactions current month (AC-DSH-01, BR-21)
        $now = now();
        $monthlyStockInsCount = StockIn::confirmed()
            ->whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->count();

        $monthlyStockOutsCount = StockOut::confirmed()
            ->whereYear('transaction_date', $now->year)
            ->whereMonth('transaction_date', $now->month)
            ->count();

        // Check if latest reconciliation found any disparity (FR-DSH-06)
        $latestReconciliation = ActivityLog::where('action', 'system.reconcile')->latest('id')->first();
        $hasReconciliationDisparity = false;
        if ($latestReconciliation && is_array($latestReconciliation->new_values)) {
            $hasReconciliationDisparity = ($latestReconciliation->new_values['discrepancies_count'] ?? 0) > 0;
        }

        return view('dashboard', [
            'isStaff' => false,
            'activeProductsCount' => $activeProductsCount,
            'activeCategoriesCount' => $activeCategoriesCount,
            'activeSuppliersCount' => $activeSuppliersCount,
            'availableStockProductsCount' => $availableStockProductsCount,
            'monthlyStockInsCount' => $monthlyStockInsCount,
            'monthlyStockOutsCount' => $monthlyStockOutsCount,
            'lowStockProductsCount' => $lowStockProductsCount,
            'outOfStockProductsCount' => $outOfStockProductsCount,
            'criticalProducts' => $criticalProducts,
            'recentTransactions' => $recentTransactions,
            'hasReconciliationDisparity' => $hasReconciliationDisparity,
        ]);
    }

    /**
     * AJAX endpoint for confirmed transaction count chart (FR-DSH-02, PRD §12.3).
     * Staff Gudang (SG) is denied access with 403.
     */
    public function chart(Request $request): JsonResponse
    {
        Gate::authorize('dashboard.view');

        if ($request->user()?->hasRole('staff_gudang')) {
            abort(403, 'Anda tidak memiliki hak akses melihat grafik transaksi global.');
        }

        $period = $request->string('period', 'daily')->toString();
        if (! in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'daily';
        }

        // Cache chart data for 60 seconds (PRD §5.3)
        $cacheKey = "dashboard.chart.{$period}";
        $data = Cache::remember($cacheKey, 60, fn () => $this->buildChartData($period));

        return response()->json($data);
    }

    /**
     * Retrieve 10 latest transactions (mixed StockIn & StockOut).
     *
     * @return list<array{type: string, type_label: string, number: string, date: string, creator_name: string, status: string, status_label: string, url: string, created_at: CarbonInterface}>
     */
    protected function getRecentTransactions(?int $userId = null): array
    {
        $inQuery = StockIn::with('creator')->latest('created_at')->limit(10);
        $outQuery = StockOut::with('creator')->latest('created_at')->limit(10);

        if ($userId !== null) {
            $inQuery->where('created_by', $userId);
            $outQuery->where('created_by', $userId);
        }

        $stockIns = $inQuery->get()->map(function (StockIn $in) {
            return [
                'type' => 'in',
                'type_label' => 'Masuk',
                'number' => $in->number,
                'date' => $in->transaction_date->isoFormat('D MMM Y'),
                'creator_name' => $in->creator ? $in->creator->name : '—',
                'status' => $in->status->value,
                'status_label' => $in->status->label(),
                'url' => route('stock-ins.show', $in),
                'created_at' => $in->created_at,
            ];
        });

        $stockOuts = $outQuery->get()->map(function (StockOut $out) {
            return [
                'type' => 'out',
                'type_label' => 'Keluar',
                'number' => $out->number,
                'date' => $out->transaction_date->isoFormat('D MMM Y'),
                'creator_name' => $out->creator ? $out->creator->name : '—',
                'status' => $out->status->value,
                'status_label' => $out->status->label(),
                'url' => route('stock-outs.show', $out),
                'created_at' => $out->created_at,
            ];
        });

        return $stockIns->concat($stockOuts)
            ->sortByDesc(fn ($item) => $item['created_at'])
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * Build chart dataset comparing confirmed transaction counts (BR-21, BR-27).
     *
     * @return array{labels: list<string>, stock_in: list<int>, stock_out: list<int>}
     */
    protected function buildChartData(string $period): array
    {
        $labels = [];
        $stockInData = [];
        $stockOutData = [];

        if ($period === 'monthly') {
            // Last 12 months
            for ($i = 11; $i >= 0; $i--) {
                $monthDate = now()->subMonths($i);
                $labels[] = $monthDate->isoFormat('MMM Y');
                $start = $monthDate->copy()->startOfMonth()->toDateString();
                $end = $monthDate->copy()->endOfMonth()->toDateString();

                $stockInData[] = StockIn::confirmed()
                    ->whereBetween('transaction_date', [$start, $end])
                    ->count();

                $stockOutData[] = StockOut::confirmed()
                    ->whereBetween('transaction_date', [$start, $end])
                    ->count();
            }
        } elseif ($period === 'weekly') {
            // Last 12 weeks
            for ($i = 11; $i >= 0; $i--) {
                $weekDate = now()->subWeeks($i);
                $start = $weekDate->copy()->startOfWeek();
                $end = $weekDate->copy()->endOfWeek();
                $labels[] = $start->format('d/m').'-'.$end->format('d/m');

                $stockInData[] = StockIn::confirmed()
                    ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                    ->count();

                $stockOutData[] = StockOut::confirmed()
                    ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                    ->count();
            }
        } else {
            // Daily: last 14 days
            for ($i = 13; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $labels[] = Carbon::parse($date)->format('d/m');

                $stockInData[] = StockIn::confirmed()
                    ->whereDate('transaction_date', $date)
                    ->count();

                $stockOutData[] = StockOut::confirmed()
                    ->whereDate('transaction_date', $date)
                    ->count();
            }
        }

        return [
            'labels' => $labels,
            'stock_in' => $stockInData,
            'stock_out' => $stockOutData,
        ];
    }
}
