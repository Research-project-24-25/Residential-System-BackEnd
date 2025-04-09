<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ApartmentFactory extends Factory
{
  public function definition(): array
  {
    return [
      'floor_id' => \App\Models\Floor::factory(),
      'number' => fake()->unique()->numberBetween(101, 999),
      'price' => fake()->randomFloat(2, 50000, 500000), // Random price between 50,000 and 500,000
      'currency' => 'USD',
      'price_type' => fake()->randomElement(['sale', 'rent_monthly']),
      'status' => fake()->randomElement(['available', 'rented', 'sold', 'coming_soon']),
      'description' => fake()->sentence(),
      'bedrooms' => fake()->numberBetween(1, 5),
      'bathrooms' => fake()->numberBetween(1, 3),
      'area' => fake()->numberBetween(50, 300), // Random area between 50 and 300
      'images' => json_encode([fake()->imageUrl(), fake()->imageUrl()]),
      'features' => json_encode(['balcony', 'parking', 'gym']),
    ];
  }
}
