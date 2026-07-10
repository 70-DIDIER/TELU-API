<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 500, 100000),
            'payment_method' => fake()->randomElement(['flooz', 'tmoney', 'card']),
            'reference_type' => 'order',
            'reference_id' => Order::factory(),
            'status' => fake()->randomElement(['pending', 'success', 'failed', 'refunded']),
            'transaction_id' => fake()->optional()->uuid(),
        ];
    }

    /**
     * Payment for a reservation.
     */
    public function forReservation(string $reservationId): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_type' => 'reservation',
            'reference_id' => $reservationId,
        ]);
    }

    /**
     * Payment for a subscription.
     */
    public function forSubscription(string $subscriptionId): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_type' => 'subscription',
            'reference_id' => $subscriptionId,
        ]);
    }

    /**
     * A successful payment.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'transaction_id' => (string) Str::uuid(),
        ]);
    }
}
