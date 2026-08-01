<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_catalogue_only_lists_available_properties(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());
        $visible = Property::factory()->create(['is_available' => true]);
        $hidden = Property::factory()->create(['is_available' => false]);

        $this->getJson('/api/properties')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id);

        $this->getJson("/api/properties/{$hidden->id}")->assertNotFound();
    }

    public function test_the_catalogue_can_be_filtered(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());
        Property::factory()->create([
            'is_available' => true,
            'property_type' => 'studio',
            'price' => 30000,
            'bedrooms' => 1,
        ]);
        Property::factory()->create([
            'is_available' => true,
            'property_type' => 'house',
            'price' => 200000,
            'bedrooms' => 4,
        ]);

        $this->getJson('/api/properties?property_type=studio')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.property_type', 'studio');

        $this->getJson('/api/properties?bedrooms=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.property_type', 'house');
    }

    public function test_an_owner_can_publish_a_property(): void
    {
        $user = User::factory()->type('property_owner')->create();
        $owner = PropertyOwner::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/property-owner/properties', [
            'title' => 'Studio meublé à Tokoin',
            'property_type' => 'studio',
            'address' => 'Tokoin, Lomé',
            'price' => 45000,
            'price_unit' => 'month',
        ])->assertCreated()->assertJsonPath('owner_id', $owner->id);
    }

    public function test_a_user_without_an_owner_profile_cannot_publish(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->postJson('/api/property-owner/properties', [
            'title' => 'X',
            'property_type' => 'studio',
            'address' => 'Y',
            'price' => 1000,
            'price_unit' => 'night',
        ])->assertForbidden();
    }

    public function test_another_owners_property_is_unreachable(): void
    {
        $user = User::factory()->type('property_owner')->create();
        PropertyOwner::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $foreign = Property::factory()->create();

        $this->getJson("/api/property-owner/properties/{$foreign->id}")->assertNotFound();
        $this->putJson("/api/property-owner/properties/{$foreign->id}", ['title' => 'Volé'])->assertNotFound();
        $this->deleteJson("/api/property-owner/properties/{$foreign->id}")->assertNotFound();
    }
}
