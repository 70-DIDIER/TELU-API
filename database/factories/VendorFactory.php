<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use App\Support\TogoCatalog;
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
            'shop_name' => TogoCatalog::shopName(),
            'description' => fake()->optional()->sentence(),
            'address' => TogoCatalog::address(),
            'latitude' => fake()->latitude(6.1, 6.25),
            'longitude' => fake()->longitude(1.1, 1.35),
            'is_active' => fake()->boolean(85),
        ];
    }
}
