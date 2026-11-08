<?php

use App\Exceptions\LockTimeoutException;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureUserIsActive::class,
            EnsurePasswordChanged::class,
        ]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (QueryException $e, Request $request) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = $e->errorInfo[1] ?? null;
            // 1213 = ER_LOCK_DEADLOCK, 1205 = ER_LOCK_WAIT_TIMEOUT, 40001 = SQLSTATE deadlock
            if ($driverCode === 1213 || $driverCode === 1205 || $sqlState === '40001') {
                $timeoutException = new LockTimeoutException;

                return $timeoutException->render($request);
            }

            return null;
        });
    })->create();
