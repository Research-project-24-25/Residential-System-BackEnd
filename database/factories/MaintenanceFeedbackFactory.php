<?php

namespace Database\Factories;

use App\Models\MaintenanceRequest;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaintenanceFeedback>
 */
class MaintenanceFeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rating = $this->faker->numberBetween(1, 5);
        $isPositive = $rating >= 4;

        return [
            'maintenance_request_id' => MaintenanceRequest::factory()->completed()->completedWithFeedback(),
            'resident_id' => Resident::factory(),
            'rating' => $rating,
            'comments' => $this->faker->paragraph(),
            'improvement_suggestions' => $isPositive ? $this->faker->optional(0.3)->paragraph() : $this->faker->paragraph(),
            'resolved_satisfactorily' => $isPositive ? true : $this->faker->boolean(30), // 30% chance for low ratings
            'would_recommend' => $isPositive ? true : $this->faker->boolean(20), // 20% chance for low ratings
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($feedback) {
            // Ensure the related maintenance request has has_feedback set to true
            $feedback->maintenanceRequest()->update(['has_feedback' => true]);
        });
    }

    /**
     * Indicate that the feedback is positive.
     */
    public function positive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rating' => $this->faker->numberBetween(4, 5),
                'resolved_satisfactorily' => true,
                'would_recommend' => true,
            ];
        });
    }

    /**
     * Indicate that the feedback is negative.
     */
    public function negative(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rating' => $this->faker->numberBetween(1, 2),
                'resolved_satisfactorily' => false,
                'would_recommend' => false,
                'improvement_suggestions' => $this->faker->paragraph(),
            ];
        });
    }

    /**
     * Indicate that the feedback is neutral.
     */
    public function neutral(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'rating' => 3,
                'resolved_satisfactorily' => $this->faker->boolean(),
                'would_recommend' => $this->faker->boolean(),
            ];
        });
    }
}
