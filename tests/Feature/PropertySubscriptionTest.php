<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertySubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function owner(bool $subscribed = false): PropertyOwner
    {
        $user = User::factory()->type('property_owner')->create();
        $attributes = ['user_id' => $user->id];

        if ($subscribed) {
            $plan = Subscription::factory()->create(['subscriber_type' => 'property_owner']);
            $attributes += [
                'subscription_id' => $plan->id,
                'subscription_started_at' => now(),
                'subscription_expires_at' => now()->addDays(30),
            ];
        }

        $owner = PropertyOwner::factory()->create($attributes);
        Sanctum::actingAs($user);

        return $owner;
    }

    public function test_a_non_subscribed_owner_is_blocked_beyond_the_free_quota(): void
    {
        Setting::set('property_free_quota', 2, 'integer');
        $owner = $this->owner();

        Property::factory()->count(2)->create(['owner_id' => $owner->id]);

        $this->postJson('/api/property-owner/properties', [
            'title' => 'Studio de trop',
            'property_type' => 'studio',
            'address' => 'Lomé',
            'price' => 20000,
            'price_unit' => 'month',
        ])->assertForbidden();
    }

    public function test_a_non_subscribed_owner_can_publish_within_the_quota(): void
    {
        Setting::set('property_free_quota', 2, 'integer');
        $owner = $this->owner();

        Property::factory()->create(['owner_id' => $owner->id]);

        $this->postJson('/api/property-owner/properties', [
            'title' => 'Studio',
            'property_type' => 'studio',
            'address' => 'Lomé',
            'price' => 20000,
            'price_unit' => 'month',
        ])->assertCreated();
    }

    public function test_a_subscribed_owner_publishes_without_limit(): void
    {
        Setting::set('property_free_quota', 1, 'integer');
        $owner = $this->owner(subscribed: true);

        Property::factory()->count(5)->create(['owner_id' => $owner->id]);

        $this->postJson('/api/property-owner/properties', [
            'title' => 'Appartement supplémentaire',
            'property_type' => 'apartment',
            'address' => 'Lomé',
            'price' => 60000,
            'price_unit' => 'month',
        ])->assertCreated();
    }

    public function test_subscribed_owners_listings_are_featured_first(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $free = $this->owner();
        $subscribed = $this->owner(subscribed: true);

        $oldFeatured = Property::factory()->create([
            'owner_id' => $subscribed->id,
            'is_available' => true,
            'created_at' => now()->subDays(5),
        ]);
        $recentFree = Property::factory()->create([
            'owner_id' => $free->id,
            'is_available' => true,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/properties')->assertOk();

        // The featured (subscribed) listing comes first even though it's older.
        $response->assertJsonPath('data.0.id', $oldFeatured->id);
        $response->assertJsonPath('data.0.is_featured', true);
        $response->assertJsonPath('data.1.id', $recentFree->id);
        $response->assertJsonPath('data.1.is_featured', false);
    }
}
