<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LastSuperAdminException extends Exception
{
    public function __construct(string $message = 'Harus ada minimal satu Super Admin aktif.')
    {
        parent::__construct($message, 422);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'LAST_SUPER_ADMIN',
            ], 422);
        }

        return back()->withInput()->with('error', $this->getMessage())->withErrors([
            'role' => $this->getMessage(),
            'status' => $this->getMessage(),
        ]);
    }
}
