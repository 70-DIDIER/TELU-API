<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->type('client')->create();
        Sanctum::actingAs($this->client);
    }

    private function property(array $attributes = []): Property
    {
        return Property::factory()->create($attributes + [
            'is_available' => true,
            'price' => 10000,
            'price_unit' => 'night',
        ]);
    }

    private function book(Property $property, int $inDays, int $outDays): TestResponse
    {
        return $this->postJson('/api/reservations', [
            'property_id' => $property->id,
            'check_in' => now()->addDays($inDays)->toDateString(),
            'check_out' => now()->addDays($outDays)->toDateString(),
        ]);
    }

    public function test_a_nightly_reservation_is_billed_per_night(): void
    {
        $property = $this->property(['price' => 10000, 'price_unit' => 'night']);

        $this->book($property, 5, 8)
            ->assertCreated()
            ->assertJsonPath('status', 'pending');

        // 3 nights × 10 000.
        $this->assertEquals(30000.0, (float) Reservation::first()->total_price);

        // The owner is notified of the request.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $property->owner->user_id,
            'type' => 'reservation',
        ]);
    }

    public function test_a_monthly_reservation_rounds_up_to_whole_months(): void
    {
        $property = $this->property(['price' => 50000, 'price_unit' => 'month']);

        // 45 nights → ceil(45/30) = 2 months.
        $this->book($property, 1, 46)->assertCreated();

        $this->assertEquals(100000.0, (float) Reservation::first()->total_price);
    }

    public function test_a_short_monthly_stay_is_billed_at_least_one_month(): void
    {
        $property = $this->property(['price' => 50000, 'price_unit' => 'month']);

        $this->book($property, 1, 11)->assertCreated();

        $this->assertEquals(50000.0, (float) Reservation::first()->total_price);
    }

    public function test_overlapping_an_active_reservation_is_rejected(): void
    {
        $property = $this->property();
        Reservation::factory()->create([
            'property_id' => $property->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $this->book($property, 8, 12)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('check_in');
    }

    public function test_checking_in_on_an_existing_check_out_day_is_allowed(): void
    {
        $property = $this->property();
        Reservation::factory()->create([
            'property_id' => $property->id,
            'status' => 'confirmed',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        // Half-open interval: the check-out day is free for the next guest.
        $this->book($property, 10, 12)->assertCreated();
    }

    public function test_a_cancelled_reservation_does_not_block_the_dates(): void
    {
        $property = $this->property();
        Reservation::factory()->create([
            'property_id' => $property->id,
            'status' => 'cancelled',
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(10)->toDateString(),
        ]);

        $this->book($property, 6, 9)->assertCreated();
    }

    public function test_an_unavailable_property_cannot_be_booked(): void
    {
        $property = $this->property(['is_available' => false]);

        $this->book($property, 5, 8)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('property_id');
    }

    public function test_a_customer_can_cancel_a_pending_reservation(): void
    {
        $reservation = Reservation::factory()->create([
            'customer_id' => $this->client->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/reservations/{$reservation->id}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $reservation->property->owner->user_id,
            'type' => 'reservation',
        ]);
    }

    public function test_a_completed_reservation_cannot_be_cancelled(): void
    {
        $reservation = Reservation::factory()->create([
            'customer_id' => $this->client->id,
            'status' => 'completed',
        ]);

        $this->postJson("/api/reservations/{$reservation->id}/cancel")->assertUnprocessable();
    }

    public function test_a_customer_only_sees_their_own_reservations(): void
    {
        $mine = Reservation::factory()->create(['customer_id' => $this->client->id]);
        $foreign = Reservation::factory()->create();

        $this->getJson('/api/reservations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);

        $this->getJson("/api/reservations/{$foreign->id}")->assertNotFound();
    }
}
