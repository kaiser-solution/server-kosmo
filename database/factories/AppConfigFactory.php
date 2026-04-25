<?php

namespace Database\Factories;

use App\Models\AppConfig;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppConfig>
 */
class AppConfigFactory extends Factory
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
            'display_name' => $this->faker->company(),
            'primary_color' => '#'.str_pad(dechex($this->faker->numberBetween(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
            'secondary_color' => '#'.str_pad(dechex($this->faker->numberBetween(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
            'default_currency' => $this->faker->randomElement(['BRL', 'USD', 'EUR', 'GBP']),
        ];
    }
}
