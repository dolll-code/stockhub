<?php

namespace Database\Factories;

use App\Models\DocumentSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSequence>
 */
class DocumentSequenceFactory extends Factory
{
    protected $model = DocumentSequence::class;

    public function definition(): array
    {
        return [
            'sequence_key' => 'IN-'.date('Ym'),
            'last_number' => 0,
        ];
    }
}
