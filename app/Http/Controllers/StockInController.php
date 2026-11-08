<?php

namespace App\Http\Controllers;

use App\Enums\StockInSource;
use App\Enums\TransactionStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Http\Requests\StockCancelRequest;
use App\Http\Requests\StockInStoreRequest;
use App\Http\Requests\StockInUpdateRequest;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockInService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockInController extends Controller
{
    /**
     * Display a listing of Stock In transactions (FR-STI-06, BR-23).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StockIn::class);

        $query = StockIn::with(['supplier', 'creator'])->latest('transaction_date')->latest('id');

        // BR-23: Scope "own" for Staff Gudang (SG)
        if ($request->user()?->hasRole('staff_gudang')) {
            $query->where('created_by', $request->user()->id);
        } elseif ($request->filled('user_id')) {
            $query->where('created_by', $request->integer('user_id'));
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->string('source_type')->toString());
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }

        if ($request->filled('number')) {
            $query->where('number', 'like', '%'.$request->string('number')->trim().'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->string('date_to')->toString());
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $stockIns = $query->paginate($perPage)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        return view('stock_ins.index', [
            'stockIns' => $stockIns,
            'suppliers' => $suppliers,
            'users' => $users,
            'canViewPrice' => $canViewPrice,
        ]);
    }

    /**
     * Show form for creating a new draft Stock In transaction.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', StockIn::class);

        $suppliers = Supplier::active()->orderBy('name')->get();
        $products = Product::with('unit')->active()->orderBy('name')->get();
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        return view('stock_ins.create', [
            'suppliers' => $suppliers,
            'products' => $products,
            'sources' => StockInSource::cases(),
            'canViewPrice' => $canViewPrice,
        ]);
    }

    /**
     * Store newly created draft Stock In.
     */
    public function store(StockInStoreRequest $request, StockInService $stockInService): RedirectResponse
    {
        Gate::authorize('create', StockIn::class);

        /** @var User $actor */
        $actor = $request->user();

        $stockIn = $stockInService->createDraft(
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('stock-ins.show', $stockIn)
            ->with('success', "Draft barang masuk [{$stockIn->number}] berhasil dibuat.");
    }

    /**
     * Display the specified Stock In transaction.
     */
    public function show(StockIn $stockIn, Request $request): View
    {
        // BR-23: Scope "own" for Staff Gudang (returns 404 if not own)
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockIn->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('view', $stockIn);

        $stockIn->load(['details.product.unit', 'supplier', 'creator', 'confirmer', 'canceller']);
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        return view('stock_ins.show', [
            'stockIn' => $stockIn,
            'canViewPrice' => $canViewPrice,
        ]);
    }

    /**
     * Show form for editing an existing draft Stock In.
     */
    public function edit(StockIn $stockIn, Request $request): View
    {
        // BR-23: Scope "own" for SG
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockIn->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('update', $stockIn);

        if ($stockIn->status !== TransactionStatus::DRAFT) {
            throw new InvalidStateTransitionException('Hanya transaksi berstatus draft yang dapat diubah.');
        }

        $stockIn->load('details.product.unit');
        $suppliers = Supplier::where('is_active', true)
            ->orWhere('id', $stockIn->supplier_id)
            ->orderBy('name')
            ->get();
        $products = Product::with('unit')->active()->orderBy('name')->get();
        $canViewPrice = $request->user()?->can('products.view-price') ?? false;

        return view('stock_ins.edit', [
            'stockIn' => $stockIn,
            'suppliers' => $suppliers,
            'products' => $products,
            'sources' => StockInSource::cases(),
            'canViewPrice' => $canViewPrice,
        ]);
    }

    /**
     * Update draft Stock In transaction.
     */
    public function update(StockInUpdateRequest $request, StockIn $stockIn, StockInService $stockInService): RedirectResponse
    {
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockIn->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('update', $stockIn);

        /** @var User $actor */
        $actor = $request->user();

        $stockInService->updateDraft(
            stockIn: $stockIn,
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('stock-ins.show', $stockIn)
            ->with('success', "Draft barang masuk [{$stockIn->number}] berhasil diperbarui.");
    }

    /**
     * Confirm draft Stock In and update stock via StockService (FR-STI-03, BR-08).
     */
    public function confirm(Request $request, StockIn $stockIn, StockService $stockService): RedirectResponse
    {
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockIn->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('confirm', $stockIn);

        /** @var User $actor */
        $actor = $request->user();

        $stockService->confirmStockIn(
            stockIn: $stockIn,
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('stock-ins.show', $stockIn)
            ->with('success', "Transaksi barang masuk [{$stockIn->number}] berhasil dikonfirmasi. Stok persediaan telah bertambah.");
    }

    /**
     * Cancel draft or confirmed Stock In transaction (BR-13, BR-14).
     */
    public function cancel(StockCancelRequest $request, StockIn $stockIn, StockService $stockService): RedirectResponse
    {
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockIn->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('cancel', $stockIn);

        /** @var User $actor */
        $actor = $request->user();
        $reason = $request->string('cancel_reason')->toString();

        if ($stockIn->status === TransactionStatus::DRAFT) {
            $stockService->cancelDraftStockIn(
                stockIn: $stockIn,
                reason: $reason,
                actor: $actor,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            return redirect()->route('stock-ins.show', $stockIn)
                ->with('success', "Draft barang masuk [{$stockIn->number}] berhasil dibatalkan.");
        }

        if ($stockIn->status === TransactionStatus::CONFIRMED) {
            $stockService->cancelConfirmedStockIn(
                stockIn: $stockIn,
                reason: $reason,
                actor: $actor,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            return redirect()->route('stock-ins.show', $stockIn)
                ->with('success', "Transaksi barang masuk [{$stockIn->number}] berhasil dibatalkan dan stok telah disesuaikan kembali.");
        }

        throw new InvalidStateTransitionException('Transaksi yang sudah dibatalkan tidak dapat diproses lagi.');
    }
}
