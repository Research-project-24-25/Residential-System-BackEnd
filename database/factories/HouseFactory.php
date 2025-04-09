<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HouseFactory extends Factory
{
  public function definition(): array
  {
    return [
      'identifier' => fake()->unique()->uuid(),
      'price' => fake()->optional()->randomFloat(2, 50000, 1000000),
      'currency' => 'USD',
      'price_type' => fake()->randomElement(['sale', 'rent_monthly']),
      'status' => fake()->randomElement(['available', 'rented', 'sold', 'coming_soon']),
      'description' => fake()->optional()->paragraph(),
      'bedrooms' => fake()->optional()->numberBetween(1, 10),
      'bathrooms' => fake()->optional()->numberBetween(1, 5),
      'area' => fake()->optional()->numberBetween(50, 1000),
      'lot_size' => fake()->optional()->numberBetween(100, 5000),
      'property_style' => fake()->optional()->randomElement(['Villa', 'Bungalow', 'Townhouse']),
      'images' => fake()->optional()->randomElements([
          'image1.jpg', 'image2.jpg', 'image3.jpg'
      ], fake()->numberBetween(1, 3)),
      'features' => fake()->optional()->randomElements([
          'Pool', 'Garage', 'Garden', 'Fireplace'
      ], fake()->numberBetween(1, 4)),
    ];
  }
}
