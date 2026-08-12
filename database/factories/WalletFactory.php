<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'walletable_type' => Vendor::class,
            'walletable_id' => Vendor::factory(),
            'balance' => 0,
        ];
    }
}
