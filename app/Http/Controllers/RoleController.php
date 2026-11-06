<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display the read-only role and permission matrix (FR-USR-06).
     */
    public function index(Request $request): View
    {
        Gate::authorize('roles.view');

        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return view('roles.index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
