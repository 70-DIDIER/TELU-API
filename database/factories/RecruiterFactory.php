<?php

namespace Database\Factories;

use App\Models\Recruiter;
use App\Models\User;
use App\Support\TogoCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recruiter>
 */
class RecruiterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->type('recruiter'),
            'company_name' => fake()->optional()->passthrough(TogoCatalog::companyName()),
            'industry' => fake()->optional()->randomElement([
                'construction', 'agriculture', 'hospitality', 'retail', 'logistics',
            ]),
        ];
    }
}
