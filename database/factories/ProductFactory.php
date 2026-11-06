<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'code' => 'BRG-'.fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'main_supplier_id' => Supplier::factory(),
            'purchase_price' => fake()->randomFloat(2, 5000, 200000),
            'selling_price' => fake()->randomFloat(2, 10000, 300000),
            'current_stock' => 0, // BR-06: Stock balance only modified via StockService
            'min_stock' => fake()->numberBetween(1, 20),
            'storage_location' => 'Rak '.fake()->bothify('?#'),
            'description' => fake()->sentence(),
            'photo_path' => null,
            'is_active' => true,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
            'current_stock' => 0, // BR-04: current_stock must be 0 to deactivate
        ]);
    }
}
