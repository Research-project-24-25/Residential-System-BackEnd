<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BuildingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'identifier' => 'Building ' . fake()->unique()->buildingNumber(),
        ];
    }
} 