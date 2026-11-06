<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Allowed route names when must_change_password is true.
     *
     * @var list<string>
     */
    protected array $allowedRouteNames = [
        'password.change',
        'password.change.update',
        'logout',
    ];

    /**
     * Handle an incoming request.
     *
     * Per PRD §5.1 FR-AUTH-05 & AC-AUTH-04:
     * Users with must_change_password = true can only access change password and logout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            $routeName = $request->route()?->getName();

            if (! in_array($routeName, $this->allowedRouteNames, true)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Anda wajib mengganti kata sandi sementara sebelum melanjutkan.',
                        'code' => 'PASSWORD_CHANGE_REQUIRED',
                    ], 403);
                }

                return redirect()->route('password.change')->with('warning', 'Anda wajib mengganti kata sandi sementara terlebih dahulu.');
            }
        }

        return $next($request);
    }
}
