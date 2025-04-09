<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FloorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'building_id' => \App\Models\Building::factory(),
            'number' => fake()->unique()->numberBetween(1, 1000),
        ];
    }
} 