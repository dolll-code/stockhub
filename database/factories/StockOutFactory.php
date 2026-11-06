<?php

namespace Database\Factories;

use App\Enums\StockOutPurpose;
use App\Enums\TransactionStatus;
use App\Models\StockOut;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOut>
 */
class StockOutFactory extends Factory
{
    protected $model = StockOut::class;

    public function definition(): array
    {
        return [
            'number' => 'OUT-'.date('Ym').'-'.fake()->unique()->numerify('####'),
            'transaction_date' => now()->toDateString(),
            'purpose_type' => StockOutPurpose::USAGE,
            'destination' => fake()->company(),
            'notes' => fake()->sentence(),
            'status' => TransactionStatus::DRAFT,
            'created_by' => User::factory(),
            'confirmed_by' => null,
            'cancelled_by' => null,
            'confirmed_at' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => TransactionStatus::CONFIRMED,
            'confirmed_by' => User::factory(),
            'confirmed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => TransactionStatus::CANCELLED,
            'cancelled_by' => User::factory(),
            'cancelled_at' => now(),
            'cancel_reason' => 'Dibatalkan oleh admin',
        ]);
    }
}
