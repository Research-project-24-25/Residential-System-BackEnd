<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropertyService>
 */
class PropertyServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'billing_type' => $this->faker->randomElement(['fixed', 'area_based', 'prepaid']),
            'price' => $this->faker->randomFloat(2, 50, 1000),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending_payment', 'expired']),
            'details' => json_encode([
                'notes' => $this->faker->sentence(),
                'terms' => $this->faker->paragraph()
            ]),
            'activated_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'expires_at' => $this->faker->optional(0.5)->dateTimeBetween('+1 month', '+2 years'),
            'last_billed_at' => $this->faker->optional(0.6)->dateTimeBetween('-6 months', 'now'),
        ];
    }
}