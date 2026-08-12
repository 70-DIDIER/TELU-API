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
            // Lorem Picsum (fiable, images photo réalistes) plutôt que le
            // imageUrl() de Faker (déprécié, pointe vers via.placeholder.com qui
            // est régulièrement indisponible) — seed unique = image stable et
            // toujours présente pour tester le rendu du catalogue.
            'image_url' => 'https://picsum.photos/seed/'.fake()->uuid().'/800/600',
            'stock' => fake()->numberBetween(0, 200),
            'is_available' => fake()->boolean(90),
        ];
    }
}
