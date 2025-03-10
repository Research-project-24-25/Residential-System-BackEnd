<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminFactory extends Factory
{
  public function definition(): array
  {
    return [
      'username' => fake()->unique()->userName(),
      'email' => fake()->unique()->safeEmail(),
      'password' => Hash::make('password'), // Default password for testing
      'role' => fake()->randomElement(['super_admin', 'admin']),
      'first_name' => fake()->firstName(),
      'last_name' => fake()->lastName(),
      'phone_number' => fake()->phoneNumber(),
      'age' => fake()->numberBetween(25, 60),
      'gender' => fake()->randomElement(['male', 'female']),
    ];
  }
}
