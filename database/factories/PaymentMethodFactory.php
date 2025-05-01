<?php

namespace Database\Factories;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['credit_card', 'debit_card', 'bank_transfer', 'cash', 'check', 'wallet']);
        $providers = [
            'credit_card' => ['Visa', 'MasterCard', 'American Express', 'Discover'],
            'debit_card' => ['Visa', 'MasterCard'],
            'bank_transfer' => ['Bank of America', 'Chase', 'Wells Fargo', 'Citibank'],
            'cash' => [null],
            'check' => [null],
            'wallet' => ['PayPal', 'Venmo', 'Cash App', 'Apple Pay', 'Google Pay'],
        ];

        $provider = $this->faker->randomElement($providers[$type]);
        $isCard = in_array($type, ['credit_card', 'debit_card']);

        return [
            'resident_id' => Resident::factory(),
            'type' => $type,
            'provider' => $provider,
            'account_number' => $isCard ? $this->faker->creditCardNumber() : $this->faker->optional()->iban('US'),
            'last_four' => $isCard ? substr($this->faker->creditCardNumber(), -4) : null,
            'expiry_date' => $isCard ? $this->faker->dateTimeBetween('+1 month', '+5 years') : null,
            'cardholder_name' => $isCard ? $this->faker->name() : null,
            'is_default' => $this->faker->boolean(20), // 20% chance of being default
            'is_verified' => $this->faker->boolean(70), // 70% chance of being verified
            'status' => $this->faker->randomElement(['active', 'inactive', 'expired', 'cancelled']),
            'metadata' => $this->faker->optional(0.2)->words(3), // 20% chance of having metadata
        ];
    }

    /**
     * Indicate that the payment method is a credit card.
     */
    public function creditCard(): static
    {
        return $this->state(function (array $attributes) {
            $cardNumber = $this->faker->creditCardNumber();
            return [
                'type' => 'credit_card',
                'provider' => $this->faker->randomElement(['Visa', 'MasterCard', 'American Express', 'Discover']),
                'account_number' => $cardNumber,
                'last_four' => substr($cardNumber, -4),
                'expiry_date' => $this->faker->dateTimeBetween('+1 month', '+5 years'),
                'cardholder_name' => $this->faker->name(),
            ];
        });
    }

    /**
     * Indicate that the payment method is active.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }
}