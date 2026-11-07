<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class StockService
{
    /**
     * Record an opening balance stock movement and set initial current_stock (BR-06, BR-17).
     * This primitive is the single authorized writer to `products.current_stock`.
     *
     * @throws InvalidArgumentException|RuntimeException
     */
    public function recordOpeningBalance(
        Product $product,
        float|string $openingStock,
        User $actor,
        ?string $notes = 'Saldo awal registrasi barang'
    ): ?StockMovement {
        $qty = (float) $openingStock;

        // BR-17: If opening_stock = 0, do not create movement
        if ($qty <= 0) {
            return null;
        }

        $execute = function () use ($product, $qty, $actor, $notes): StockMovement {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::with('unit')->where('id', $product->id)->lockForUpdate()->firstOrFail();

            // Validate decimal rules per unit (BR-01)
            $unit = $lockedProduct->unit;
            if ($unit && ! $unit->allow_decimal) {
                if ($qty != (int) $qty) {
                    throw new InvalidArgumentException("Satuan {$unit->name} tidak mengizinkan nilai desimal.");
                }
            } else {
                // Max 3 decimal places
                $parts = explode('.', (string) $qty);
                if (isset($parts[1]) && strlen($parts[1]) > 3) {
                    throw new InvalidArgumentException('Kuantitas stok awal maksimal memiliki 3 angka di belakang koma.');
                }
            }

            // Invariant checks: opening balance is only allowed once on initial registration
            if ((float) $lockedProduct->current_stock !== 0.0) {
                throw new RuntimeException('Saldo awal hanya dapat dicatat saat registrasi barang dengan saldo nol.');
            }

            if ($lockedProduct->stockMovements()->exists()) {
                throw new RuntimeException('Barang sudah memiliki mutasi stok; saldo awal tidak dapat dicatat.');
            }

            $before = 0.000;
            $change = $qty;
            $after = $qty;

            // 1. Create exactly one immutable stock movement
            $movement = StockMovement::create([
                'product_id' => $lockedProduct->id,
                'movement_type' => MovementType::OPENING_BALANCE,
                'reference_type' => null,
                'reference_id' => null,
                'reference_number' => null,
                'movement_date' => now()->toDateString(),
                'qty_before' => $before,
                'qty_change' => $change,
                'qty_after' => $after,
                'user_id' => $actor->id,
                'notes' => $notes ?: 'Saldo awal registrasi barang',
            ]);

            // 2. Set products.current_stock (BR-06: StockService is sole writer)
            $lockedProduct->forceFill([
                'current_stock' => $after,
            ])->save();

            // Update in-memory instance as well
            $product->current_stock = $after;

            return $movement;
        };

        if (DB::transactionLevel() > 0) {
            return $execute();
        }

        return DB::transaction($execute);
    }
}
