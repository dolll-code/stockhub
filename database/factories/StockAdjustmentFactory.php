<?php

namespace Database\Factories;

use App\Enums\AdjustmentReason;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    protected $model = StockAdjustment::class;

    public function definition(): array
    {
        $systemQty = 10.000;
        $physicalQty = 8.000;
        $difference = $physicalQty - $systemQty; // -2.000

        return [
            'number' => 'ADJ-'.date('Ym').'-'.fake()->unique()->numerify('####'),
            'adjustment_date' => now()->toDateString(),
            'product_id' => Product::factory(),
            'system_qty' => $systemQty,
            'physical_qty' => $physicalQty,
            'difference' => $difference,
            'reason_code' => AdjustmentReason::DAMAGED,
            'notes' => 'Penyesuaian stok rusak saat pemindahan barang',
            'created_by' => User::factory(),
        ];
    }
}
