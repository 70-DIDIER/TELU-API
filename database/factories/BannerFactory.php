<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_url' => fake()->imageUrl(800, 400, 'business'),
            'link_url' => fake()->optional()->url(),
            'title' => fake()->sentence(3),
            'position' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
