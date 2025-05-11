<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Bill;
// PaymentMethod model is removed
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
        $status = $this->faker->randomElement(['paid', 'refunded']);
        // Both 'paid' and 'refunded' are considered processed states where a transaction_id and payment_date would exist.
        $isProcessed = true; // Simplified as 'no-paid' is removed

        return [
            'bill_id' => Bill::factory(),
            'resident_id' => Resident::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 1000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'status' => $status,
            'transaction_id' => $isProcessed ? $this->faker->uuid() : null, // or always generate if status is paid/refunded
            // receipt_url removed
            'payment_date' => $isProcessed ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'notes' => $this->faker->optional(0.3)->sentence(),
            'metadata' => $this->faker->optional(0.2)->words(3),
            'processed_by' => $isProcessed ? Admin::factory() : null,
        ];
    }

    /**
     * Indicate that the payment is paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
                'transaction_id' => $this->faker->uuid(),
                'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'processed_by' => Admin::factory(),
            ];
        });
    }

    /**
     * Indicate that the payment is refunded.
     */
    public function refunded(): static
    {
        return $this->state(function (array $attributes) {
            // When a payment is refunded, it might still have a transaction_id, payment_date, etc.
            // The amount might be positive on the original record, with a separate negative transaction for the refund.
            return [
                'status' => 'refunded',
                'transaction_id' => $this->faker->uuid(), // Original transaction_id
                'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
                'processed_by' => Admin::factory(),
                 // metadata might be updated to indicate refund details
                'metadata' => array_merge($attributes['metadata'] ?? [], [
                    'refunded_at' => now(),
                    'refund_reason' => $this->faker->sentence()
                ]),
            ];
        });
    }
}
