<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+228 9# ## ## ##'),
            'profile_photo' => null,
            'user_type' => fake()->randomElement([
                'client', 'vendor', 'driver', 'property_owner', 'recruiter', 'job_seeker',
            ]),
            'current_latitude' => fake()->latitude(6.1, 6.25),
            'current_longitude' => fake()->longitude(1.1, 1.35),
            'is_verified' => fake()->boolean(70),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'is_verified' => false,
        ]);
    }

    /**
     * Give the user a specific type.
     */
    public function type(string $userType): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => $userType,
        ]);
    }
}
