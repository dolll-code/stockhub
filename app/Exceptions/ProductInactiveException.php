<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductInactiveException extends Exception
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message = 'Barang sudah nonaktif.',
        protected array $errors = []
    ) {
        parent::__construct($message, 422);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'PRODUCT_INACTIVE',
                'errors' => $this->errors,
            ], 422);
        }

        return back()->withInput()->with('error', $this->getMessage())->withErrors($this->errors);
    }
}
