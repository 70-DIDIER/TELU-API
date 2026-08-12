<?php

namespace Database\Factories;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 500, 20000);

        return [
            'wallet_id' => Wallet::factory(),
            'type' => 'credit',
            'amount' => $amount,
            'balance_after' => $amount,
            'reference_type' => 'order',
            'reference_id' => null,
            'description' => fake()->sentence(),
        ];
    }
}
