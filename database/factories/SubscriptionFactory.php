<?php

namespace Database\Factories;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Premium', 'Pro']),
            'price' => fake()->randomFloat(2, 1000, 25000),
            'duration_days' => fake()->randomElement([7, 30, 90, 365]),
            'features' => fake()->sentence(),
            'subscriber_type' => fake()->randomElement(['vendor', 'driver']),
        ];
    }
}
