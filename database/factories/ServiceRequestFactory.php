<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Property;
use App\Models\Resident;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'approved', 'scheduled', 'in_progress', 'completed', 'cancelled']);
        $isAdminHandled = in_array($status, ['approved', 'scheduled', 'in_progress', 'completed']);
        $isCompleted = $status === 'completed';

        return [
            'service_id' => Service::factory(),
            'property_id' => Property::factory(),
            'resident_id' => Resident::factory(),
            'description' => $this->faker->paragraph(),
            'requested_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'scheduled_date' => ($status === 'scheduled' || $status === 'in_progress' || $isCompleted) 
                ? $this->faker->dateTimeBetween('now', '+1 month') 
                : null,
            'completion_date' => $isCompleted 
                ? $this->faker->dateTimeBetween('-1 week', 'now') 
                : null,
            'status' => $status,
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'admin_id' => $isAdminHandled ? Admin::factory() : null,
            'estimated_cost' => $this->faker->optional(0.8)->randomFloat(2, 50, 500),
            'final_cost' => $isCompleted 
                ? $this->faker->randomFloat(2, 50, 800) 
                : null,
            'bill_id' => null, // Will be set by the seeder if needed
        ];
    }

    /**
     * Indicate that the service request is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'scheduled_date' => null,
                'completion_date' => null,
                'admin_id' => null,
                'final_cost' => null,
            ];
        });
    }

    /**
     * Indicate that the service request is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'scheduled_date' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
                'completion_date' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'admin_id' => Admin::factory(),
                'final_cost' => $this->faker->randomFloat(2, 50, 800),
            ];
        });
    }
}