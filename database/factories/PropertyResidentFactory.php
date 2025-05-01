<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropertyResident>
 */
class PropertyResidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $relationshipType = $this->faker->randomElement(['buyer', 'co_buyer', 'renter']);
        $isBuyer = in_array($relationshipType, ['buyer', 'co_buyer']);

        return [
            'property_id' => Property::factory(),
            'resident_id' => Resident::factory(),
            'relationship_type' => $relationshipType,
            'sale_price' => $isBuyer ? $this->faker->randomFloat(2, 100000, 1000000) : null,
            'ownership_share' => $relationshipType === 'co_buyer' ? $this->faker->randomFloat(2, 10, 90) : null,
            'monthly_rent' => $relationshipType === 'renter' ? $this->faker->randomFloat(2, 500, 5000) : null,
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end_date' => $relationshipType === 'renter' ? $this->faker->optional(0.7)->dateTimeBetween('+1 month', '+2 years') : null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterMaking(function ($propertyResident) {
            // Here we could add any custom logic if needed
        });
    }

    /**
     * Indicate that the resident is a buyer.
     */
    public function buyer(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'relationship_type' => 'buyer',
                'sale_price' => $this->faker->randomFloat(2, 100000, 1000000),
                'ownership_share' => null,
                'monthly_rent' => null,
            ];
        });
    }

    /**
     * Indicate that the resident is a renter.
     */
    public function renter(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'relationship_type' => 'renter',
                'sale_price' => null,
                'ownership_share' => null,
                'monthly_rent' => $this->faker->randomFloat(2, 500, 5000),
                'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
                'end_date' => $this->faker->dateTimeBetween('+6 months', '+2 years'),
            ];
        });
    }
}