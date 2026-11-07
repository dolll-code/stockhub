<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $query = Product::with(['category', 'unit', 'mainSupplier']);

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

        // Status filter (active/inactive)
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

        // Sorting (name, created_at, current_stock)
        $sortBy = $request->string('sort_by')->toString();
        $order = strtolower($request->string('order')->toString()) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'name') {
            $query->orderBy('name', $order);
        } elseif ($sortBy === 'stock' || $sortBy === 'current_stock') {
            $query->orderBy('current_stock', $order);
        } else {
            $query->orderBy('created_at', $order);
        }

        // Pagination (15, 25, 50, 100)
        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $products = $query->paginate($perPage)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'canViewPrice' => $canViewPrice,
        ]);
    }

    public function show(Product $product, Request $request): View
    {
        Gate::authorize('view', $product);

        $product->load(['category', 'unit', 'mainSupplier', 'creator', 'updater']);

        // FR-PRD-04: compact stock card with 20 latest stock movements
        $recentMovements = $product->stockMovements()
            ->with('user')
            ->latest('id')
            ->limit(20)
            ->get();

        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        return view('products.show', [
            'product' => $product,
            'recentMovements' => $recentMovements,
            'canViewPrice' => $canViewPrice,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Product::class);

        $categories = Category::active()->orderBy('name')->get();
        $units = Unit::active()->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        return view('products.create', [
            'categories' => $categories,
            'units' => $units,
            'suppliers' => $suppliers,
            'canViewPrice' => $canViewPrice,
        ]);
    }

    public function store(ProductStoreRequest $request, ProductService $productService): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        /** @var User $actor */
        $actor = $request->user();

        $product = $productService->create(
            data: $request->validated(),
            photo: $request->file('photo'),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('products.index')
            ->with('success', "Barang [{$product->name}] ({$product->code}) berhasil ditambahkan.");
    }

    public function edit(Product $product, Request $request): View
    {
        Gate::authorize('update', $product);

        $categories = Category::where('is_active', true)
            ->orWhere('id', $product->category_id)
            ->orderBy('name')
            ->get();

        $units = Unit::where('is_active', true)
            ->orWhere('id', $product->unit_id)
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('is_active', true)
            ->orWhere('id', $product->main_supplier_id)
            ->orderBy('name')
            ->get();

        $isUnitLocked = $product->stockMovements()->exists();
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
            'units' => $units,
            'suppliers' => $suppliers,
            'isUnitLocked' => $isUnitLocked,
            'canViewPrice' => $canViewPrice,
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product, ProductService $productService): RedirectResponse
    {
        Gate::authorize('update', $product);

        /** @var User $actor */
        $actor = $request->user();

        $productService->update(
            product: $product,
            data: $request->validated(),
            photo: $request->file('photo'),
            removePhoto: $request->boolean('remove_photo'),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('products.index')
            ->with('success', "Barang [{$product->name}] ({$product->code}) berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, Product $product, ProductService $productService): RedirectResponse
    {
        Gate::authorize('toggleStatus', $product);

        /** @var User $actor */
        $actor = $request->user();

        $productService->toggleStatus(
            product: $product,
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        $statusStr = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Barang [{$product->name}] ({$product->code}) berhasil {$statusStr}.");
    }

    public function deletePhoto(Request $request, Product $product, ProductService $productService): RedirectResponse
    {
        Gate::authorize('update', $product);

        /** @var User $actor */
        $actor = $request->user();

        $productService->deletePhoto(
            product: $product,
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return back()->with('success', 'Foto barang berhasil dihapus.');
    }
}
