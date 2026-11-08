<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockChangedException extends Exception
{
    public function __construct(
        string $message = 'Stok sistem telah berubah. Periksa kembali sebelum menyimpan.',
        protected ?float $currentStock = null
    ) {
        parent::__construct($message, 409);
    }

    public function getCurrentStock(): ?float
    {
        return $this->currentStock;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'STOCK_CHANGED',
                'current_stock' => $this->currentStock,
            ], 409);
        }

        return back()->withInput()->with('error', $this->getMessage())->withErrors([
            'expected_system_qty' => $this->getMessage(),
        ]);
    }
}
