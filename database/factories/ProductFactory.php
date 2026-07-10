<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 100, 50000),
            'category' => fake()->randomElement(['food', 'drinks', 'grocery', 'electronics', 'fashion']),
            'image_url' => fake()->optional()->imageUrl(),
            'stock' => fake()->numberBetween(0, 200),
            'is_available' => fake()->boolean(90),
        ];
    }
}
