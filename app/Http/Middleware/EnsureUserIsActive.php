<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * Per PRD §5.1 FR-AUTH-08 & AC-AUTH-05:
     * Inactive user session is terminated immediately on the next request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.',
                    'code' => 'ACCOUNT_INACTIVE',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Email atau kata sandi yang Anda masukkan salah.',
            ]);
        }

        return $next($request);
    }
}
