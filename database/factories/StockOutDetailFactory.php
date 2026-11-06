<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockOut;
use App\Models\StockOutDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOutDetail>
 */
class StockOutDetailFactory extends Factory
{
    protected $model = StockOutDetail::class;

    public function definition(): array
    {
        return [
            'stock_out_id' => StockOut::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->randomFloat(3, 1, 20),
        ];
    }
}
