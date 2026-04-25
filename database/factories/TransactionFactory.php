<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
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
            'type' => $this->faker->randomElement(['expense', 'income', 'transfer']),
            'amount' => $this->faker->randomFloat(2, 1, 10000),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['alimentação', 'transporte', 'lazer', 'saúde', 'moradia']),
            'occurred_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'metadata' => null,
        ];
    }
}
