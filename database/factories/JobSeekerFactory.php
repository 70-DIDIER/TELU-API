<?php

namespace Database\Factories;

use App\Models\JobSeeker;
use App\Models\User;
use App\Support\TogoCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobSeeker>
 */
class JobSeekerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->type('job_seeker'),
            'profession' => TogoCatalog::jobTitle(),
            'skills' => fake()->optional()->words(5, true),
            'experience' => fake()->optional()->sentence(),
            'availability' => fake()->randomElement(['immediate', 'within a week', 'within a month']),
        ];
    }
}
