<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->type('driver'),
            'vehicle_type' => fake()->randomElement(['moto', 'tricycle', 'car', 'van']),
            'license_number' => fake()->optional()->bothify('??-####'),
            'coverage_zone' => fake()->optional()->city(),
            'is_available' => fake()->boolean(70),
            'current_latitude' => fake()->latitude(6.1, 6.25),
            'current_longitude' => fake()->longitude(1.1, 1.35),
        ];
    }
}
