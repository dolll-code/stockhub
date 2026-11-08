<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    /**
     * Display global stock movement ledger (FR-STK-03).
     * Read-only access for SA, AG, MG; Staff Gudang is denied 403 (AC-STK-05).
     */
    public function index(Request $request): View
    {
        Gate::authorize('stock-movements.view');

        $query = StockMovement::with(['product.unit', 'user'])
            ->latest('id');

        // Search product code or name
        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->trim().'%';
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('code', 'like', $search)
                    ->orWhere('name', 'like', $search);
            });
        }

        // Movement type filter
        if ($request->filled('type')) {
            $query->where('movement_type', $request->string('type')->toString());
        }

        // Date range filters
        if ($request->filled('date_from')) {
            $query->whereDate('movement_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('movement_date', '<=', $request->string('date_to')->toString());
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $movements = $query->paginate($perPage)->withQueryString();
        $users = User::orderBy('name')->get();
        $movementTypes = MovementType::cases();

        return view('stock_movements.index', [
            'movements' => $movements,
            'users' => $users,
            'movementTypes' => $movementTypes,
        ]);
    }
}
