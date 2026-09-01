<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyOwner;
use App\Support\TogoCatalog;
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
        $type = fake()->randomElement(['room', 'studio', 'apartment', 'house', 'hotel_room']);
        $labels = [
            'room' => 'Chambre à louer',
            'studio' => 'Studio meublé',
            'apartment' => 'Appartement',
            'house' => 'Villa',
            'hotel_room' => 'Chambre d\'hôtel',
        ];
        $quartier = TogoCatalog::lomeQuartier();

        return [
            'owner_id' => PropertyOwner::factory(),
            'title' => $labels[$type].' à '.$quartier,
            'description' => fake()->optional()->paragraph(),
            'property_type' => $type,
            'address' => TogoCatalog::address($quartier),
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
