<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobSeeker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_offer_id' => JobOffer::factory(),
            'job_seeker_id' => JobSeeker::factory(),
            'status' => fake()->randomElement(['pending', 'accepted', 'rejected', 'completed']),
            'applied_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
