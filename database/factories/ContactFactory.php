<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
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
            'type' => $this->faker->randomElement(['supplier', 'client']),
            'name' => $this->faker->company(),
            'phone' => $this->faker->numerify('55119########'),
            'phone2' => null,
            'email' => $this->faker->optional()->safeEmail(),
            'category' => $this->faker->optional()->word(),
            'active' => true,
            'payload' => null,
        ];
    }
}
