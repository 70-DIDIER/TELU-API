<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'driver_id' => null,
            'status' => 'awaiting_driver',
            'delivery_fee' => fake()->randomFloat(2, 0, 3000),
            'pickup_time' => null,
            'delivery_time' => null,
            'assigned_at' => null,
        ];
    }

    /**
     * A delivery that has been assigned to a driver.
     */
    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_id' => Driver::factory(),
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);
    }

    /**
     * A completed delivery.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_id' => Driver::factory(),
            'status' => 'delivered',
            'assigned_at' => now()->subHours(2),
            'pickup_time' => now()->subHour(),
            'delivery_time' => now(),
        ]);
    }
}
