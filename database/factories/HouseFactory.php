<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HouseFactory extends Factory
{
  public function definition(): array
  {
    return [
      'house_number' => fake()->unique()->numberBetween(1, 100),
      'number_of_residents' => fake()->numberBetween(1, 6),
      'house_type' => fake()->randomElement(['villa', 'house']),
      'is_occupied' => fake()->boolean(),
    ];
  }
}
