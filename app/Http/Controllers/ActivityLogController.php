<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Display activity audit logs (FR-LOG-01..03).
     * Read-only and strictly restricted to Super Admin.
     */
    public function index(Request $request): View
    {
        Gate::authorize('activity-logs.view');

        $query = ActivityLog::with('user')->latest('id');

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', $search)
                    ->orWhere('action', 'like', $search);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action')->toString());
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', 'like', '%'.$request->string('subject_type')->trim().'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to')->toString());
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $logs = $query->paginate($perPage)->withQueryString();
        $users = User::orderBy('name')->get();
        $distinctActions = ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('activity_logs.index', [
            'logs' => $logs,
            'users' => $users,
            'distinctActions' => $distinctActions,
        ]);
    }
}
