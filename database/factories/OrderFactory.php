<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Support\TogoCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'customer_id' => User::factory()->type('client'),
            'status' => fake()->randomElement([
                'pending', 'accepted', 'preparing', 'in_delivery', 'delivered', 'cancelled',
            ]),
            'total_amount' => fake()->randomFloat(2, 500, 100000),
            'delivery_address' => TogoCatalog::address(),
            'delivery_latitude' => fake()->latitude(6.1, 6.25),
            'delivery_longitude' => fake()->longitude(1.1, 1.35),
        ];
    }
}
