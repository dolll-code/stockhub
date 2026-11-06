<?php

namespace Database\Factories;

use App\Enums\StockInSource;
use App\Enums\TransactionStatus;
use App\Models\StockIn;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockIn>
 */
class StockInFactory extends Factory
{
    protected $model = StockIn::class;

    public function definition(): array
    {
        return [
            'number' => 'IN-'.date('Ym').'-'.fake()->unique()->numerify('####'),
            'transaction_date' => now()->toDateString(),
            'source_type' => StockInSource::PURCHASE,
            'supplier_id' => Supplier::factory(),
            'reference_document' => 'SJ-'.fake()->numerify('######'),
            'notes' => fake()->sentence(),
            'status' => TransactionStatus::DRAFT,
            'total_amount' => 0,
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
            'cancel_reason' => 'Dibatalkan karena salah input',
        ]);
    }
}
