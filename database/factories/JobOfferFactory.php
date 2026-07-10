<?php

namespace Database\Factories;

use App\Models\JobOffer;
use App\Models\Recruiter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobOffer>
 */
class JobOfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recruiter_id' => Recruiter::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->optional()->paragraph(),
            'location' => fake()->city(),
            'latitude' => fake()->latitude(6.1, 6.25),
            'longitude' => fake()->longitude(1.1, 1.35),
            'daily_rate' => fake()->randomFloat(2, 2000, 30000),
            'required_skills' => fake()->optional()->words(4, true),
            'start_date' => fake()->dateTimeBetween('now', '+2 months'),
            'duration' => fake()->randomElement(['1 day', '1 week', '1 month', 'ongoing']),
            'people_needed' => fake()->numberBetween(1, 10),
            'is_active' => fake()->boolean(85),
        ];
    }
}
