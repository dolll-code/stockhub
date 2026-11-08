<?php

namespace App\Http\Controllers;

use App\Enums\StockOutPurpose;
use App\Enums\TransactionStatus;
use App\Exceptions\InvalidStateTransitionException;
use App\Http\Requests\StockCancelRequest;
use App\Http\Requests\StockOutStoreRequest;
use App\Http\Requests\StockOutUpdateRequest;
use App\Models\Product;
use App\Models\StockOut;
use App\Models\User;
use App\Services\StockOutService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockOutController extends Controller
{
    /**
     * Display a listing of Stock Out transactions (FR-STO-06, BR-23).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StockOut::class);

        $query = StockOut::with('creator')->latest('transaction_date')->latest('id');

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

        if ($request->filled('purpose_type')) {
            $query->where('purpose_type', $request->string('purpose_type')->toString());
        }

        if ($request->filled('destination')) {
            $query->where('destination', 'like', '%'.$request->string('destination')->trim().'%');
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

        $stockOuts = $query->paginate($perPage)->withQueryString();
        $users = User::orderBy('name')->get();

        return view('stock_outs.index', [
            'stockOuts' => $stockOuts,
            'users' => $users,
            'purposes' => StockOutPurpose::cases(),
        ]);
    }

    /**
     * Show form for creating a new draft Stock Out transaction.
     */
    public function create(): View
    {
        Gate::authorize('create', StockOut::class);

        $products = Product::with('unit')->active()->orderBy('name')->get();

        return view('stock_outs.create', [
            'products' => $products,
            'purposes' => StockOutPurpose::cases(),
        ]);
    }

    /**
     * Store newly created draft Stock Out.
     */
    public function store(StockOutStoreRequest $request, StockOutService $stockOutService): RedirectResponse
    {
        Gate::authorize('create', StockOut::class);

        /** @var User $actor */
        $actor = $request->user();

        $stockOut = $stockOutService->createDraft(
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('stock-outs.show', $stockOut)
            ->with('success', "Draft barang keluar [{$stockOut->number}] berhasil dibuat.");
    }

    /**
     * Display the specified Stock Out transaction.
     */
    public function show(StockOut $stockOut, Request $request): View
    {
        // BR-23: Scope "own" for Staff Gudang (returns 404 if not own)
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockOut->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('view', $stockOut);

        $stockOut->load(['details.product.unit', 'creator', 'confirmer', 'canceller']);

        return view('stock_outs.show', [
            'stockOut' => $stockOut,
        ]);
    }

    /**
     * Show form for editing an existing draft Stock Out.
     */
    public function edit(StockOut $stockOut, Request $request): View
    {
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockOut->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('update', $stockOut);

        if ($stockOut->status !== TransactionStatus::DRAFT) {
            throw new InvalidStateTransitionException('Hanya transaksi berstatus draft yang dapat diubah.');
        }

        $stockOut->load('details.product.unit');
        $products = Product::with('unit')->active()->orderBy('name')->get();

        return view('stock_outs.edit', [
            'stockOut' => $stockOut,
            'products' => $products,
            'purposes' => StockOutPurpose::cases(),
        ]);
    }

    /**
     * Update draft Stock Out transaction.
     */
    public function update(StockOutUpdateRequest $request, StockOut $stockOut, StockOutService $stockOutService): RedirectResponse
    {
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockOut->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('update', $stockOut);

        /** @var User $actor */
        $actor = $request->user();

        $stockOutService->updateDraft(
            stockOut: $stockOut,
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('stock-outs.show', $stockOut)
            ->with('success', "Draft barang keluar [{$stockOut->number}] berhasil diperbarui.");
    }

    /**
     * Confirm draft Stock Out and update stock via StockService (FR-STO-03, BR-08, BR-10).
     */
    public function confirm(Request $request, StockOut $stockOut, StockService $stockService): RedirectResponse
    {
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockOut->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('confirm', $stockOut);

        /** @var User $actor */
        $actor = $request->user();

        $stockService->confirmStockOut(
            stockOut: $stockOut,
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('stock-outs.show', $stockOut)
            ->with('success', "Transaksi barang keluar [{$stockOut->number}] berhasil dikonfirmasi. Stok persediaan telah dikurangi.");
    }

    /**
     * Cancel draft or confirmed Stock Out transaction (BR-13, BR-14).
     */
    public function cancel(StockCancelRequest $request, StockOut $stockOut, StockService $stockService): RedirectResponse
    {
        if ($request->user()?->hasRole('staff_gudang') && (int) $stockOut->created_by !== (int) $request->user()->id) {
            abort(404, 'Data transaksi tidak ditemukan.');
        }

        Gate::authorize('cancel', $stockOut);

        /** @var User $actor */
        $actor = $request->user();
        $reason = $request->string('cancel_reason')->toString();

        if ($stockOut->status === TransactionStatus::DRAFT) {
            $stockService->cancelDraftStockOut(
                stockOut: $stockOut,
                reason: $reason,
                actor: $actor,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            return redirect()->route('stock-outs.show', $stockOut)
                ->with('success', "Draft barang keluar [{$stockOut->number}] berhasil dibatalkan.");
        }

        if ($stockOut->status === TransactionStatus::CONFIRMED) {
            $stockService->cancelConfirmedStockOut(
                stockOut: $stockOut,
                reason: $reason,
                actor: $actor,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            return redirect()->route('stock-outs.show', $stockOut)
                ->with('success', "Transaksi barang keluar [{$stockOut->number}] berhasil dibatalkan dan stok telah dipulihkan kembali.");
        }

        throw new InvalidStateTransitionException('Transaksi yang sudah dibatalkan tidak dapat diproses lagi.');
    }
}
