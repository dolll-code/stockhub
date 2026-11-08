<?php

namespace App\Http\Controllers;

use App\Enums\AdjustmentReason;
use App\Http\Requests\StockAdjustmentStoreRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of stock adjustments (FR-ADJ-02).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StockAdjustment::class);

        $query = StockAdjustment::with(['product.unit', 'creator'])->latest('adjustment_date')->latest('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('reason_code')) {
            $query->where('reason_code', $request->string('reason_code')->toString());
        }

        if ($request->filled('user_id')) {
            $query->where('created_by', $request->integer('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('adjustment_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('adjustment_date', '<=', $request->string('date_to')->toString());
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $adjustments = $query->paginate($perPage)->withQueryString();
        $products = Product::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('adjustments.index', [
            'adjustments' => $adjustments,
            'products' => $products,
            'users' => $users,
            'reasons' => AdjustmentReason::cases(),
        ]);
    }

    /**
     * Show form for creating a new stock adjustment.
     */
    public function create(): View
    {
        Gate::authorize('create', StockAdjustment::class);

        $products = Product::with('unit')->active()->orderBy('name')->get();

        return view('adjustments.create', [
            'products' => $products,
            'reasons' => AdjustmentReason::cases(),
        ]);
    }

    /**
     * Store and post a newly created stock adjustment (FR-ADJ-01, BR-16).
     */
    public function store(StockAdjustmentStoreRequest $request, StockService $stockService): RedirectResponse
    {
        Gate::authorize('create', StockAdjustment::class);

        /** @var User $actor */
        $actor = $request->user();

        $adjustment = $stockService->createAdjustment(
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('adjustments.show', $adjustment)
            ->with('success', "Penyesuaian stok opname [{$adjustment->number}] berhasil diposting.");
    }

    /**
     * Display the specified stock adjustment.
     */
    public function show(StockAdjustment $adjustment): View
    {
        Gate::authorize('view', $adjustment);

        $adjustment->load(['product.unit', 'creator']);

        return view('adjustments.show', [
            'adjustment' => $adjustment,
        ]);
    }
}
