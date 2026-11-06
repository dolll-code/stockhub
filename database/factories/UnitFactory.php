<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'symbol' => fake()->unique()->lexify('???'),
            'description' => fake()->sentence(),
            'allow_decimal' => false,
            'is_active' => true,
        ];
    }

    public function decimal(): static
    {
        return $this->state(fn () => ['allow_decimal' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
