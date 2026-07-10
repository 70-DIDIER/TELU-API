<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['order', 'delivery', 'reservation', 'job', 'payment', 'system']),
            'message' => fake()->sentence(),
            'is_read' => fake()->boolean(30),
        ];
    }
}
