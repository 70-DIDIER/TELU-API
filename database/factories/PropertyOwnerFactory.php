<?php

namespace Database\Factories;

use App\Models\PropertyOwner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyOwner>
 */
class PropertyOwnerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ownerType = fake()->randomElement(['individual', 'hotel']);

        return [
            'user_id' => User::factory()->type('property_owner'),
            'owner_type' => $ownerType,
            'company_name' => $ownerType === 'hotel' ? fake()->company() : null,
        ];
    }
}
