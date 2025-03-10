<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ApartmentFactory extends Factory
{
  public function definition(): array
  {
    return [
      'floor_id' => \App\Models\Floor::factory(),
      'apartment_number' => fake()->unique()->numberBetween(101, 999),
    ];
  }
}
