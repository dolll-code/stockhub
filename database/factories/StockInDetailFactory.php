<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockInDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockInDetail>
 */
class StockInDetailFactory extends Factory
{
    protected $model = StockInDetail::class;

    public function definition(): array
    {
        return [
            'stock_in_id' => StockIn::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->randomFloat(3, 1, 50),
            'unit_price' => fake()->randomFloat(2, 5000, 100000),
        ];
    }
}
