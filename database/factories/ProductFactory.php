<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Vendor;
use App\Support\TogoCatalog;
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
        $product = TogoCatalog::product();

        return [
            'vendor_id' => Vendor::factory(),
            'name' => $product['name'],
            'description' => fake()->optional()->sentence(),
            'price' => fake()->randomFloat(2, 100, 50000),
            'category' => $product['category'],
            // Vraie photo du produit (Wikimedia Commons, résolue par nom dans
            // TogoCatalog) plutôt qu'une image Lorem Picsum aléatoire sans
            // rapport avec ce qui est vendu.
            'image_url' => $product['image_url'],
            'stock' => fake()->numberBetween(0, 200),
            'is_available' => fake()->boolean(90),
        ];
    }
}
