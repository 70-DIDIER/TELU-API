<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyOwner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => PropertyOwner::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'property_type' => fake()->randomElement(['room', 'studio', 'apartment', 'house', 'hotel_room']),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(6.1, 6.25),
            'longitude' => fake()->longitude(1.1, 1.35),
            'price' => fake()->randomFloat(2, 5000, 500000),
            'price_unit' => fake()->randomElement(['night', 'month']),
            'bedrooms' => fake()->numberBetween(1, 5),
            // 3 images Lorem Picsum, séparées par virgule (format attendu par le
            // mobile : image_urls.split(',') pour la galerie du détail du bien).
            'image_urls' => collect(range(1, 3))
                ->map(fn () => 'https://picsum.photos/seed/'.fake()->uuid().'/900/600')
                ->implode(','),
            'is_available' => fake()->boolean(80),
        ];
    }
}
