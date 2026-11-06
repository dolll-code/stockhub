<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $request->authenticate($logger);

        // Regenerate session ID to prevent session fixation (PRD §5.1 FR-AUTH-02, §14.1 SEC-03)
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $logger->log(
                action: 'auth.logout',
                description: "Pengguna [{$user->name}] keluar dari sistem",
                subject: $user,
                actor: $user,
                oldValues: null,
                newValues: null,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
