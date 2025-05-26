<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Property;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bill>
 */
class BillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'resident_id' => Resident::factory(),
            'bill_type' => $this->faker->randomElement(['rent', 'utility', 'service_charge', 'maintenance']),
            'amount' => $this->faker->randomFloat(2, 50, 1000),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'description' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['pending', 'paid', 'overdue', 'cancelled']),
            'recurrence' => $this->faker->optional(0.2)->randomElement(['monthly', 'quarterly', 'annually']), // 20% chance of being recurring
            'next_billing_date' => function (array $attributes) {
                return $attributes['recurrence'] ? $this->faker->dateTimeBetween('+1 month', '+1 year') : null;
            },
            'created_by' => Admin::factory(),
        ];
    }
}
