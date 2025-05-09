<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['utility', 'security', 'cleaning', 'other']),
            'base_price' => $this->faker->randomFloat(2, 50, 500),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'unit_of_measure' => $this->faker->randomElement(['hour', 'visit', 'month', 'unit', null]),
            'is_recurring' => $this->faker->boolean(30), // 30% chance of being recurring
            'recurrence' => function (array $attributes) {
                return $attributes['is_recurring'] 
                    ? $this->faker->randomElement(['monthly', 'quarterly', 'yearly']) 
                    : null;
            },
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'metadata' => $this->faker->optional(0.2)->words(3), // 20% chance of having metadata
        ];
    }
}