<?php

namespace Tests\Feature;

use App\Models\PropertyOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPropertyOwnerVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->type('admin')->create());
    }

    public function test_an_admin_can_verify_an_owners_documents(): void
    {
        $owner = PropertyOwner::factory()->create(['verification_status' => 'pending']);

        $this->patchJson("/api/admin/property-owners/{$owner->id}/verification", [
            'verification_status' => 'verified',
        ])->assertOk()->assertJsonPath('verification_status', 'verified');

        $this->assertNotNull($owner->fresh()->verified_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $owner->user_id, 'type' => 'verification']);
    }

    public function test_rejecting_without_a_reason_is_rejected(): void
    {
        $owner = PropertyOwner::factory()->create();

        $this->patchJson("/api/admin/property-owners/{$owner->id}/verification", [
            'verification_status' => 'rejected',
        ])->assertUnprocessable()->assertJsonValidationErrors('verification_notes');
    }

    public function test_an_unknown_owner_returns_404(): void
    {
        $this->patchJson('/api/admin/property-owners/00000000-0000-0000-0000-000000000000/verification', [
            'verification_status' => 'verified',
        ])->assertNotFound();
    }

    public function test_a_non_admin_cannot_review_kyc(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());
        $owner = PropertyOwner::factory()->create();

        $this->patchJson("/api/admin/property-owners/{$owner->id}/verification", [
            'verification_status' => 'verified',
        ])->assertForbidden();
    }
}
