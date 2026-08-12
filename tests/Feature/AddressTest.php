<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_create_a_saved_address(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/addresses', [
            'label' => 'Maison',
            'address' => 'Agoè Zongo, rue des Palmiers, Lomé',
            'latitude' => 6.1725,
            'longitude' => 1.2314,
        ])
            ->assertCreated()
            ->assertJsonPath('label', 'Maison')
            ->assertJsonPath('latitude', '6.1725000')
            ->assertJsonPath('is_default', true);
    }

    public function test_the_first_address_is_always_the_default_even_if_not_requested(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/addresses', [
            'label' => 'Maison',
            'address' => 'Bè, Lomé',
            'is_default' => false,
        ])->assertCreated()->assertJsonPath('is_default', true);
    }

    public function test_creating_a_second_default_address_unsets_the_previous_default(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $first = $this->postJson('/api/addresses', ['label' => 'Maison', 'address' => 'Bè, Lomé'])
            ->assertCreated()
            ->json();

        $this->postJson('/api/addresses', [
            'label' => 'Bureau',
            'address' => 'Adidogomé, Lomé',
            'is_default' => true,
        ])->assertCreated()->assertJsonPath('is_default', true);

        $this->assertFalse(Address::find($first['id'])->is_default);
    }

    public function test_a_user_can_list_their_addresses_default_first(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Address::create(['user_id' => $user->id, 'label' => 'Maison', 'address' => 'Bè', 'is_default' => false]);
        Address::create(['user_id' => $user->id, 'label' => 'Bureau', 'address' => 'Adidogomé', 'is_default' => true]);

        $response = $this->getJson('/api/addresses')->assertOk()->json();

        $this->assertCount(2, $response);
        $this->assertSame('Bureau', $response[0]['label']);
    }

    public function test_a_user_only_sees_their_own_addresses(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Address::create(['user_id' => $other->id, 'label' => 'Maison', 'address' => 'Bè', 'is_default' => true]);
        Sanctum::actingAs($user);

        $this->getJson('/api/addresses')->assertOk()->assertJsonCount(0);
    }

    public function test_a_user_can_update_one_of_their_addresses(): void
    {
        $user = User::factory()->create();
        $address = Address::create(['user_id' => $user->id, 'label' => 'Maison', 'address' => 'Bè', 'is_default' => true]);
        Sanctum::actingAs($user);

        $this->putJson("/api/addresses/{$address->id}", ['label' => 'Domicile'])
            ->assertOk()
            ->assertJsonPath('label', 'Domicile');
    }

    public function test_updating_someone_elses_address_returns_404(): void
    {
        $owner = User::factory()->create();
        $address = Address::create(['user_id' => $owner->id, 'label' => 'Maison', 'address' => 'Bè', 'is_default' => true]);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/addresses/{$address->id}", ['label' => 'Domicile'])->assertNotFound();
    }

    public function test_a_user_can_delete_an_address_and_the_default_is_promoted(): void
    {
        $user = User::factory()->create();
        $first = Address::create(['user_id' => $user->id, 'label' => 'Maison', 'address' => 'Bè', 'is_default' => true]);
        $second = Address::create(['user_id' => $user->id, 'label' => 'Bureau', 'address' => 'Adidogomé', 'is_default' => false]);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/addresses/{$first->id}")->assertOk();

        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_a_user_can_set_a_different_default_address(): void
    {
        $user = User::factory()->create();
        $first = Address::create(['user_id' => $user->id, 'label' => 'Maison', 'address' => 'Bè', 'is_default' => true]);
        $second = Address::create(['user_id' => $user->id, 'label' => 'Bureau', 'address' => 'Adidogomé', 'is_default' => false]);
        Sanctum::actingAs($user);

        $this->patchJson("/api/addresses/{$second->id}/default")
            ->assertOk()
            ->assertJsonPath('is_default', true);

        $this->assertFalse($first->fresh()->is_default);
    }

    public function test_creating_an_address_requires_a_label_and_an_address(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/addresses', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['label', 'address']);
    }
}
