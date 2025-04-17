<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ResidentFactory extends Factory
{
  public function definition(): array
  {
    return [
      'username' => fake()->unique()->userName(),
      'first_name' => fake()->firstName(),
      'last_name' => fake()->lastName(),
      'email' => fake()->unique()->safeEmail(),
      'password' => Hash::make('password'), // Default password for testing
      'phone_number' => fake()->phoneNumber(),
      'age' => fake()->numberBetween(18, 80),
      'gender' => fake()->randomElement(['male', 'female']),
      'created_by' => \App\Models\Admin::factory(),
    ];
  }
}
