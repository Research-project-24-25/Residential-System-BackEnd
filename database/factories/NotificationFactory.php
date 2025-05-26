<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Determine the notifiable entity randomly
        $notifiableTypes = [
            User::class,
            Admin::class,
            Resident::class,
        ];
        $notifiableType = $this->faker->randomElement($notifiableTypes);
        $notifiable = $notifiableType::factory()->create(); // Create the related model instance

        return [
            'type' => $this->faker->randomElement([ // Example notification types
                \App\Notifications\NewMeetingRequest::class,
                \App\Notifications\MeetingRequestStatusChanged::class,
            ]),
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiable->id,
            'data' => $this->faker->text(200),
            'read_at' => $this->faker->optional(0.3)->dateTimeThisMonth, // 30% chance of being read
        ];
    }
}
