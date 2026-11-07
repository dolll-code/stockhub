<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitStoreRequest;
use App\Http\Requests\UnitUpdateRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(
        protected UnitService $unitService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Unit::class);

        $query = Unit::withCount('products')->latest();

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('symbol', 'like', $search);
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

        $units = $query->paginate($perPage)->withQueryString();

        return view('units.index', [
            'units' => $units,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Unit::class);

        return view('units.create');
    }

    public function store(UnitStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Unit::class);

        /** @var User $actor */
        $actor = $request->user();

        $unit = $this->unitService->create(
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('units.index')
            ->with('success', "Satuan [{$unit->name}] ({$unit->symbol}) berhasil ditambahkan.");
    }

    public function edit(Unit $unit): View
    {
        Gate::authorize('update', $unit);

        $isLocked = $unit->products()->exists();

        return view('units.edit', [
            'unit' => $unit,
            'isLocked' => $isLocked,
        ]);
    }

    public function update(UnitUpdateRequest $request, Unit $unit): RedirectResponse
    {
        Gate::authorize('update', $unit);

        /** @var User $actor */
        $actor = $request->user();

        $this->unitService->update(
            unit: $unit,
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('units.index')
            ->with('success', "Satuan [{$unit->name}] berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, Unit $unit): RedirectResponse
    {
        Gate::authorize('toggleStatus', $unit);

        /** @var User $actor */
        $actor = $request->user();

        $this->unitService->toggleStatus(
            unit: $unit,
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        $statusStr = $unit->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Satuan [{$unit->name}] berhasil {$statusStr}.");
    }
}
