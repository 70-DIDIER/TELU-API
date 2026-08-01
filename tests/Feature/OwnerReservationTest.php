<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OwnerReservationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private PropertyOwner $owner;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->type('property_owner')->create();
        $this->owner = PropertyOwner::factory()->create(['user_id' => $this->user->id]);
        $this->property = Property::factory()->create(['owner_id' => $this->owner->id]);
        Sanctum::actingAs($this->user);
    }

    private function reservation(string $status = 'pending'): Reservation
    {
        return Reservation::factory()->create([
            'property_id' => $this->property->id,
            'status' => $status,
        ]);
    }

    public function test_a_user_without_an_owner_profile_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->getJson('/api/property-owner/reservations')->assertForbidden();
    }

    public function test_the_owner_only_sees_reservations_on_their_properties(): void
    {
        $mine = $this->reservation();
        Reservation::factory()->create(); // another owner's property

        $this->getJson('/api/property-owner/reservations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_the_listing_can_be_filtered_by_status(): void
    {
        $this->reservation('pending');
        $this->reservation('confirmed');

        $this->getJson('/api/property-owner/reservations?status=confirmed')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'confirmed');
    }

    public function test_the_owner_can_confirm_a_pending_reservation(): void
    {
        $reservation = $this->reservation('pending');

        $this->patchJson("/api/property-owner/reservations/{$reservation->id}/status", [
            'status' => 'confirmed',
        ])->assertOk()->assertJsonPath('status', 'confirmed');

        // The customer is notified of the new status.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $reservation->customer_id,
            'type' => 'reservation',
        ]);
    }

    public function test_the_owner_can_complete_a_confirmed_reservation(): void
    {
        $reservation = $this->reservation('confirmed');

        $this->patchJson("/api/property-owner/reservations/{$reservation->id}/status", [
            'status' => 'completed',
        ])->assertOk()->assertJsonPath('status', 'completed');
    }

    public function test_an_illegal_transition_is_rejected(): void
    {
        $reservation = $this->reservation('pending');

        // pending → completed skips the confirmation step.
        $this->patchJson("/api/property-owner/reservations/{$reservation->id}/status", [
            'status' => 'completed',
        ])->assertUnprocessable();

        $this->assertSame('pending', $reservation->fresh()->status);
    }

    public function test_a_reservation_on_another_owners_property_is_unreachable(): void
    {
        $foreign = Reservation::factory()->create(['status' => 'pending']);

        $this->patchJson("/api/property-owner/reservations/{$foreign->id}/status", [
            'status' => 'confirmed',
        ])->assertNotFound();
    }
}
