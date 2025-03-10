<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BuildingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Building ' . fake()->unique()->buildingNumber(),
            'address' => fake()->streetAddress(),
            'total_floors' => fake()->numberBetween(3, 20),
        ];
    }
} 