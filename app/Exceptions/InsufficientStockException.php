<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    /**
     * @param  array<string, list<string>>  $errors
     */
    public function __construct(
        string $message = 'Persediaan tidak mencukupi.',
        protected array $errors = []
    ) {
        parent::__construct($message, 422);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'INSUFFICIENT_STOCK',
                'errors' => $this->errors,
            ], 422);
        }

        return back()->withInput()->with('error', $this->getMessage())->withErrors($this->errors);
    }
}
