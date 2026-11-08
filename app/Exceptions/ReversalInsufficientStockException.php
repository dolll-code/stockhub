<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReversalInsufficientStockException extends Exception
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message = 'Pembatalan transaksi tidak dapat diproses karena sisa stok tidak mencukupi pengurangan.',
        protected array $errors = []
    ) {
        parent::__construct($message, 422);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'REVERSAL_INSUFFICIENT_STOCK',
                'errors' => $this->errors,
            ], 422);
        }

        return back()->with('error', $this->getMessage())->withErrors($this->errors);
    }
}
