<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvalidStateTransitionException extends Exception
{
    public function __construct(string $message = 'Transaksi ini sudah diproses oleh pengguna lain atau berstatus berbeda. Muat ulang halaman.')
    {
        parent::__construct($message, 409);
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'INVALID_STATE_TRANSITION',
            ], 409);
        }

        return response()->view('errors.409', [
            'exception' => $this,
        ], 409);
    }
}
