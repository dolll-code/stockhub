<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExportLimitExceededException extends Exception
{
    public function __construct(
        int $actualRows,
        int $maxLimit
    ) {
        $actualFormatted = number_format($actualRows, 0, ',', '.');
        $limitFormatted = number_format($maxLimit, 0, ',', '.');
        $message = "Data terlalu banyak ({$actualFormatted} baris, batas {$limitFormatted}). Persempit filter.";

        parent::__construct($message, 422);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'EXPORT_LIMIT_EXCEEDED',
            ], 422);
        }

        /** @var RedirectResponse $redirect */
        $redirect = back()->with('error', $this->getMessage());

        return $redirect->setStatusCode(422);
    }
}
