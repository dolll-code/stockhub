<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierStoreRequest;
use App\Http\Requests\SupplierUpdateRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $supplierService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Supplier::class);

        $query = Supplier::withCount('products')->latest();

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', $search)
                    ->orWhere('name', 'like', $search)
                    ->orWhere('contact_name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $suppliers = $query->paginate($perPage)->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
        ]);
    }

    public function show(Supplier $supplier): View
    {
        Gate::authorize('view', $supplier);

        // FR-SUP-04: Detail with 10 latest stock-in transactions
        $recentStockIns = $supplier->stockIns()
            ->with(['creator', 'details.product'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('suppliers.show', [
            'supplier' => $supplier,
            'recentStockIns' => $recentStockIns,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Supplier::class);

        return view('suppliers.create');
    }

    public function store(SupplierStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Supplier::class);

        /** @var User $actor */
        $actor = $request->user();

        $supplier = $this->supplierService->create(
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('suppliers.index')
            ->with('success', "Supplier [{$supplier->name}] ({$supplier->code}) berhasil ditambahkan.");
    }

    public function edit(Supplier $supplier): View
    {
        Gate::authorize('update', $supplier);

        return view('suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(SupplierUpdateRequest $request, Supplier $supplier): RedirectResponse
    {
        Gate::authorize('update', $supplier);

        /** @var User $actor */
        $actor = $request->user();

        $this->supplierService->update(
            supplier: $supplier,
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('suppliers.index')
            ->with('success', "Supplier [{$supplier->name}] ({$supplier->code}) berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, Supplier $supplier): RedirectResponse
    {
        Gate::authorize('toggleStatus', $supplier);

        /** @var User $actor */
        $actor = $request->user();

        $this->supplierService->toggleStatus(
            supplier: $supplier,
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        $statusStr = $supplier->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Supplier [{$supplier->name}] ({$supplier->code}) berhasil {$statusStr}.");
    }
}
