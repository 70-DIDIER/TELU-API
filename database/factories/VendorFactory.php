<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->type('vendor'),
            'subscription_id' => null,
            'shop_name' => fake()->company(),
            'description' => fake()->optional()->sentence(),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(6.1, 6.25),
            'longitude' => fake()->longitude(1.1, 1.35),
            'is_active' => fake()->boolean(85),
        ];
    }
}
