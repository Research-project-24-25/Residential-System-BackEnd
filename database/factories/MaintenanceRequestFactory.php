<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Bill;
use App\Models\Maintenance;
use App\Models\Property;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
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
        $hasFeedback = $isCompleted && $this->faker->boolean(50); // 50% chance of having feedback if completed

        return [
            'maintenance_id' => Maintenance::factory(),
            'property_id' => Property::factory(),
            'resident_id' => Resident::factory(),
            'description' => $this->faker->sentence(),
            'issue_details' => $this->faker->paragraph(),
            'images' => $this->faker->boolean(30) ? [$this->faker->imageUrl(640, 480, 'maintenance')] : [], // 30% chance of having images
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'emergency']),
            'status' => $status,
            'requested_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'scheduled_date' => ($status === 'scheduled' || $status === 'in_progress' || $isCompleted)
                ? $this->faker->dateTimeBetween('now', '+1 month')
                : null,
            'completion_date' => $isCompleted
                ? $this->faker->dateTimeBetween('-1 week', 'now')
                : null,
            'notes' => $this->faker->optional(0.7)->paragraph(),
            'admin_id' => $isAdminHandled ? Admin::factory() : null,
            'estimated_cost' => $this->faker->optional(0.8)->randomFloat(2, 50, 500),
            'final_cost' => $isCompleted
                ? $this->faker->randomFloat(2, 50, 800)
                : null,
            'bill_id' => $isCompleted && $this->faker->boolean(30) ? Bill::factory() : null, // 30% chance of having bill if completed
            'has_feedback' => $hasFeedback,
        ];
    }

    /**
     * Indicate that the maintenance request is pending.
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
                'bill_id' => null,
                'has_feedback' => false,
            ];
        });
    }

    /**
     * Indicate that the maintenance request is approved.
     */
    public function approved(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
                'scheduled_date' => null,
                'completion_date' => null,
                'admin_id' => Admin::factory(),
                'has_feedback' => false,
            ];
        });
    }

    /**
     * Indicate that the maintenance request is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'scheduled',
                'scheduled_date' => $this->faker->dateTimeBetween('now', '+1 month'),
                'completion_date' => null,
                'admin_id' => Admin::factory(),
                'has_feedback' => false,
            ];
        });
    }

    /**
     * Indicate that the maintenance request is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_progress',
                'scheduled_date' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'completion_date' => null,
                'admin_id' => Admin::factory(),
                'has_feedback' => false,
            ];
        });
    }

    /**
     * Indicate that the maintenance request is completed.
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
                'has_feedback' => false,
            ];
        });
    }

    /**
     * Indicate that the maintenance request is completed with feedback.
     */
    public function completedWithFeedback(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'scheduled_date' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
                'completion_date' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'admin_id' => Admin::factory(),
                'final_cost' => $this->faker->randomFloat(2, 50, 800),
                'has_feedback' => true,
            ];
        });
    }

    /**
     * Indicate that the maintenance request is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'scheduled_date' => null,
                'completion_date' => null,
                'admin_id' => Admin::factory(),
                'final_cost' => null,
                'bill_id' => null,
                'has_feedback' => false,
            ];
        });
    }

    /**
     * Indicate that the maintenance request is an emergency.
     */
    public function emergency(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'priority' => 'emergency',
            ];
        });
    }
}
