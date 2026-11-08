<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockController extends Controller
{
    /**
     * Display current stock status of all products (FR-STK-01).
     */
    public function index(Request $request): View
    {
        Gate::authorize('stocks.view');

        $query = Product::with(['category', 'unit']);

        // Search code or name
        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', $search)
                    ->orWhere('name', 'like', $search);
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        // Active / Inactive status filter
        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Stock status filter (BR-18)
        if ($request->filled('stock_status')) {
            $stockStatus = $request->string('stock_status')->toString();
            if ($stockStatus === 'habis' || $stockStatus === 'out_of_stock') {
                $query->where('current_stock', '<=', 0);
            } elseif ($stockStatus === 'minimum' || $stockStatus === 'low') {
                $query->where('current_stock', '>', 0)
                    ->whereColumn('current_stock', '<=', 'min_stock');
            } elseif ($stockStatus === 'aman' || $stockStatus === 'safe') {
                $query->whereColumn('current_stock', '>', 'min_stock');
            }
        }

        // Sorting
        $sortBy = $request->string('sort_by')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'name') {
            $query->orderBy('name', $order);
        } elseif ($sortBy === 'stock' || $sortBy === 'current_stock') {
            $query->orderBy('current_stock', $order);
        } else {
            $query->orderBy('created_at', $order);
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $products = $query->paginate($perPage)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        return view('stocks.index', [
            'products' => $products,
            'categories' => $categories,
            'canViewPrice' => $canViewPrice,
        ]);
    }

    /**
     * Display critical low & out-of-stock active products (FR-STK-02).
     * Ordered by criticality: out-of-stock first, then lowest stock/min ratio.
     */
    public function low(Request $request): View
    {
        Gate::authorize('stocks.view');

        // Only ACTIVE products with status 'habis' or 'minimum' (BR-04, BR-18, AC-STK-04)
        $query = Product::with(['category', 'unit'])
            ->active()
            ->whereColumn('current_stock', '<=', 'min_stock');

        // Search code or name
        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', $search)
                    ->orWhere('name', 'like', $search);
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        // Criticality order (out-of-stock first, then lowest current/min ratio, then name)
        $query->orderByRaw('CASE WHEN current_stock <= 0 THEN 0 ELSE 1 END ASC, CASE WHEN min_stock > 0 THEN current_stock / min_stock ELSE 0 END ASC, name ASC');

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $products = $query->paginate($perPage)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('stocks.low', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
