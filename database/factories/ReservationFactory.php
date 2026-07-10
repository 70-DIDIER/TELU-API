<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('now', '+1 month');
        $checkOut = (clone $checkIn)->modify('+'.fake()->numberBetween(1, 14).' days');

        return [
            'property_id' => Property::factory(),
            'customer_id' => User::factory()->type('client'),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'total_price' => fake()->randomFloat(2, 5000, 300000),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
        ];
    }
}
