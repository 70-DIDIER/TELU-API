<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawalRequest>
 */
class WithdrawalRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'amount' => fake()->randomFloat(2, 1000, 20000),
            'phone_number' => '9'.fake()->numerify('#######'),
            'status' => 'pending',
            'processed_at' => null,
            'processed_by' => null,
            'note' => null,
        ];
    }
}
