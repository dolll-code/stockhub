<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LockTimeoutException extends Exception
{
    public function __construct(string $message = 'Sistem sedang sibuk. Coba lagi beberapa saat lagi.')
    {
        parent::__construct($message, 503);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'LOCK_TIMEOUT',
            ], 503);
        }

        return back()->with('error', $this->getMessage());
    }
}
