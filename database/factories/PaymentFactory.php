<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Bill;
use App\Models\PaymentMethod;
use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'processing', 'completed', 'failed', 'refunded']);
        $isCompleted = $status === 'completed';
        $isProcessed = in_array($status, ['completed', 'failed', 'refunded']);

        return [
            'bill_id' => Bill::factory(),
            'resident_id' => Resident::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 1000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'status' => $status,
            'transaction_id' => $isProcessed ? $this->faker->uuid() : null,
            'receipt_url' => $isCompleted ? $this->faker->optional(0.7)->url() : null,
            'payment_date' => $isProcessed ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'notes' => $this->faker->optional(0.3)->sentence(),
            'metadata' => $this->faker->optional(0.2)->words(3),
            'processed_by' => $isProcessed ? Admin::factory() : null,
        ];
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'transaction_id' => $this->faker->uuid(),
                'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'processed_by' => Admin::factory(),
            ];
        });
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'transaction_id' => null,
                'receipt_url' => null,
                'payment_date' => null,
                'processed_by' => null,
            ];
        });
    }
}