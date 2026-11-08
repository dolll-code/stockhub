<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoDifferenceException extends Exception
{
    public function __construct(string $message = 'Stok fisik sama dengan stok sistem; tidak ada yang perlu disesuaikan.')
    {
        parent::__construct($message, 422);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'code' => 'NO_DIFFERENCE',
                'errors' => [
                    'physical_qty' => [$this->getMessage()],
                ],
            ], 422);
        }

        return back()->withInput()->with('error', $this->getMessage())->withErrors([
            'physical_qty' => $this->getMessage(),
        ]);
    }
}
