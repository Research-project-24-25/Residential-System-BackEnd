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
            'type' => $this->faker->randomElement(['electricity', 'gas', 'water', 'security', 'cleaning', 'other']),
            'base_price' => $this->faker->randomFloat(2, 50, 500),
            'provider_cost' => $this->faker->randomFloat(2, 20, 300),
            'unit_of_measure' => $this->faker->randomElement(['hour', 'visit', 'month', 'unit', null]),
            'is_recurring' => $this->faker->boolean(30), // 30% chance of being recurring
            'recurrence' => function (array $attributes) {
                return $attributes['is_recurring']
                    ? $this->faker->randomElement(['monthly', 'quarterly', 'yearly'])
                    : null;
            },
        ];
    }
}
