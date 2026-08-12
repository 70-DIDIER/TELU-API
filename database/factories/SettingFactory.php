<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'value' => (string) fake()->randomFloat(2, 0, 100),
            'type' => 'decimal',
            'group' => fake()->randomElement(['commerce', 'delivery', 'immobilier', 'emploi', 'paygate']),
            'description' => fake()->sentence(),
        ];
    }
}
