<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rater_id' => User::factory(),
            'target_type' => 'vendor',
            'target_id' => Vendor::factory(),
            'score' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Rate a specific target (vendor|driver|property_owner|recruiter|job_seeker).
     */
    public function forTarget(string $targetType, string $targetId): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);
    }
}
