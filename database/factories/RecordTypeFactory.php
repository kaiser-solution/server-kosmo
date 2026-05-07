<?php

namespace Database\Factories;

use App\Models\RecordType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecordType>
 */
class RecordTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'status' => 'active',
        ];
    }
}
