<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->unique()->regexify('[A-Z]{1}[0-9]{1}[A-Z]{1}[0-9]{1}[A-Z]{1}'), // e.g., B1F2A5
            'type' => $this->faker->randomElement(['apartment', 'house', 'villa']),
            'price' => $this->faker->numberBetween(100000, 1000000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'status' => $this->faker->randomElement(['available_now', 'under_construction', 'sold', 'rented']),
            'description' => $this->faker->sentence(),
            'occupancy_limit' => $this->faker->numberBetween(1, 10),
            'bedrooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->numberBetween(1, 5),
            'area' => $this->faker->numberBetween(50, 500), // in square meters
            'images' => json_encode($this->faker->imageUrl(640, 480, 'property')),
            'features' => json_encode($this->faker->words(5, true)), // array of property features
        ];
    }
}
