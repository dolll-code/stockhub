<?php

namespace Database\Factories;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $before = 10.000;
        $change = 5.000;
        $after = $before + $change;

        return [
            'product_id' => Product::factory(),
            'movement_type' => MovementType::STOCK_IN,
            'reference_type' => 'stock_in',
            'reference_id' => fake()->randomNumber(4),
            'reference_number' => 'IN-'.date('Ym').'-'.fake()->unique()->numerify('####'),
            'movement_date' => now()->toDateString(),
            'qty_before' => $before,
            'qty_change' => $change,
            'qty_after' => $after,
            'user_id' => User::factory(),
            'notes' => 'Penerimaan barang dari supplier',
            'created_at' => now(),
        ];
    }
}
