<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDriverVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->type('admin')->create());
    }

    public function test_an_admin_can_verify_a_drivers_documents(): void
    {
        $driver = Driver::factory()->create(['verification_status' => 'pending']);

        $this->patchJson("/api/admin/drivers/{$driver->id}/verification", [
            'verification_status' => 'verified',
        ])->assertOk()->assertJsonPath('verification_status', 'verified');

        $this->assertNotNull($driver->fresh()->verified_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $driver->user_id, 'type' => 'verification']);
    }

    public function test_rejecting_without_a_reason_is_rejected(): void
    {
        $driver = Driver::factory()->create();

        $this->patchJson("/api/admin/drivers/{$driver->id}/verification", [
            'verification_status' => 'rejected',
        ])->assertUnprocessable()->assertJsonValidationErrors('verification_notes');
    }

    public function test_an_unknown_driver_returns_404(): void
    {
        $this->patchJson('/api/admin/drivers/00000000-0000-0000-0000-000000000000/verification', [
            'verification_status' => 'verified',
        ])->assertNotFound();
    }

    public function test_a_non_admin_cannot_review_kyc(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());
        $driver = Driver::factory()->create();

        $this->patchJson("/api/admin/drivers/{$driver->id}/verification", [
            'verification_status' => 'verified',
        ])->assertForbidden();
    }
}
