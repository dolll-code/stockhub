<?php

namespace App\Http\Controllers;

use App\Exceptions\LastSuperAdminException;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Rules\StockHubPassword;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $query = User::with('roles')->latest();

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->trim().'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if ($request->filled('role')) {
            $query->role($request->string('role'));
        }

        if ($request->filled('status')) {
            if ($request->string('status')->toString() === 'active') {
                $query->where('is_active', true);
            } elseif ($request->string('status')->toString() === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $users = $query->paginate($perPage)->withQueryString();

        return view('users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('users.create', [
            'roles' => ['super_admin', 'admin_gudang', 'staff_gudang', 'manajer'],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserStoreRequest $request, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validated();
        $actor = $request->user();

        $user = DB::transaction(function () use ($validated, $actor, $request, $logger) {
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]);

            $newUser->syncRoles([$validated['role']]);

            $logger->log(
                action: 'user.created',
                description: "Super Admin [{$actor?->name}] membuat pengguna [{$newUser->name}]",
                subject: $newUser,
                actor: $actor,
                oldValues: null,
                newValues: [
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'role' => $validated['role'],
                    'is_active' => true,
                    'must_change_password' => true,
                ],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            return $newUser;
        });

        return redirect()->route('users.index')->with('success', "Pengguna [{$user->name}] berhasil dibuat.");
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): RedirectResponse
    {
        Gate::authorize('view', $user);

        return redirect()->route('users.edit', $user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => ['super_admin', 'admin_gudang', 'staff_gudang', 'manajer'],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validated();
        $actor = $request->user();
        $currentRole = $user->getRoleNames()->first();
        $newRole = $validated['role'];

        // BR-24: User cannot change their own role
        if ($actor && $actor->id === $user->id && $newRole !== $currentRole) {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat mengubah peran akun Anda sendiri (BR-24).',
            ]);
        }

        // BR-24: Cannot demote the last active Super Admin
        if ($currentRole === 'super_admin' && $newRole !== 'super_admin') {
            $activeSuperAdmins = User::role('super_admin')->where('is_active', true)->count();
            if ($user->is_active && $activeSuperAdmins <= 1) {
                throw new LastSuperAdminException;
            }
        }

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $currentRole,
        ];

        DB::transaction(function () use ($user, $validated, $newRole, $oldValues, $actor, $request, $logger) {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            $user->syncRoles([$newRole]);

            $logger->log(
                action: 'user.updated',
                description: "Data pengguna [{$user->name}] diperbarui",
                subject: $user,
                actor: $actor,
                oldValues: $oldValues,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $newRole,
                ],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            if ($oldValues['role'] !== $newRole) {
                $logger->log(
                    action: 'user.role_changed',
                    description: "Peran pengguna [{$user->name}] diubah dari [{$oldValues['role']}] menjadi [{$newRole}]",
                    subject: $user,
                    actor: $actor,
                    oldValues: ['role' => $oldValues['role']],
                    newValues: ['role' => $newRole],
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent()
                );
            }
        });

        return redirect()->route('users.index')->with('success', "Data pengguna [{$user->name}] berhasil diperbarui.");
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleStatus(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('toggleStatus', $user);

        $actor = $request->user();

        // BR-24: User cannot deactivate their own account
        if ($actor && $actor->id === $user->id) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat menonaktifkan akun Anda sendiri (BR-24).',
            ]);
        }

        // BR-24: Cannot deactivate the last active Super Admin
        if ($user->is_active && $user->hasRole('super_admin')) {
            $activeSuperAdmins = User::role('super_admin')->where('is_active', true)->count();
            if ($activeSuperAdmins <= 1) {
                throw new LastSuperAdminException;
            }
        }

        $newStatus = ! $user->is_active;

        DB::transaction(function () use ($user, $newStatus, $actor, $request, $logger) {
            $user->update([
                'is_active' => $newStatus,
            ]);

            // Invalidate user sessions if deactivated
            if (! $newStatus) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

            $logger->log(
                action: 'user.status_changed',
                description: "Status akun pengguna [{$user->name}] {$statusText}",
                subject: $user,
                actor: $actor,
                oldValues: ['is_active' => ! $newStatus],
                newValues: ['is_active' => $newStatus],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );
        });

        $statusMessage = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun [{$user->name}] berhasil {$statusMessage}.");
    }

    /**
     * Reset the specified user's password with a temporary password.
     */
    public function resetPassword(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('resetPassword', $user);

        $validated = $request->validate([
            'password' => ['required', 'string', new StockHubPassword($user->email)],
        ]);

        $actor = $request->user();

        DB::transaction(function () use ($user, $validated, $actor, $request, $logger) {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'must_change_password' => true,
            ])->save();

            // Invalidate all active sessions for this user per FR-USR-05 / AC-USR-03
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $logger->log(
                action: 'user.password_reset',
                description: "Kata sandi pengguna [{$user->name}] direset oleh administrator",
                subject: $user,
                actor: $actor,
                oldValues: null,
                newValues: ['must_change_password' => true],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );
        });

        return back()->with('success', "Kata sandi sementara untuk pengguna [{$user->name}] berhasil ditetapkan. Sesi pengguna tersebut telah diputus.");
    }
}
