<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Record;
use App\Models\RecordType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Record>
 */
class RecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'record_type_id' => RecordType::factory(),
            'payload' => ['value' => $this->faker->randomFloat(2, 1, 1000)],
            'occurred_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
