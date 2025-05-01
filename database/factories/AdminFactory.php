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
      'role' => 'admin', // Default to 'admin', can be overridden by states
      'first_name' => fake()->firstName(),
      'last_name' => fake()->lastName(),
      'phone_number' => fake()->phoneNumber(),
      'age' => fake()->numberBetween(25, 60),
      'gender' => fake()->randomElement(['male', 'female']),
    ];
  }

  /**
   * Indicate that the admin is a super admin.
   *
   * @return \Illuminate\Database\Eloquent\Factories\Factory
   */
  public function superAdmin()
  {
    return $this->state(function (array $attributes) {
      return [
        'role' => 'super_admin',
      ];
    });
  }

  /**
   * Indicate that the admin is a regular admin.
   *
   * @return \Illuminate\Database\Eloquent\Factories\Factory
   */
  public function admin()
  {
    return $this->state(function (array $attributes) {
      return [
        'role' => 'admin',
      ];
    });
  }
}
