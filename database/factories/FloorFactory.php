<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FloorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'building_id' => \App\Models\Building::factory(),
            'floor_number' => fake()->numberBetween(1, 20),
            'total_apartments' => fake()->numberBetween(2, 8),
        ];
    }
} 