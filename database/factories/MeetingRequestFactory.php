<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingRequest>
 */
class MeetingRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'approved', 'rejected', 'cancelled', 'completed']);
        $isAdminHandled = in_array($status, ['approved', 'rejected', 'completed']);

        return [
            'user_id' => User::factory(),
            'property_id' => Property::factory(),
            'requested_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'purpose' => $this->faker->sentence(3),
            'notes' => $this->faker->optional()->paragraph,
            'id_document' => $this->faker->optional(0.3)->imageUrl(640, 480, 'documents'), // 30% chance of having a document URL
            'status' => $status,
            'approved_date' => $status === 'approved' ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'admin_id' => $isAdminHandled ? Admin::factory() : null,
            'admin_notes' => $isAdminHandled ? $this->faker->optional()->sentence : null,
        ];
    }
}
