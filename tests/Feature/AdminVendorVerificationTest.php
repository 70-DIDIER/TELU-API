<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminVendorVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->type('admin')->create());
    }

    public function test_an_admin_can_verify_a_vendors_documents(): void
    {
        $vendor = Vendor::factory()->create(['verification_status' => 'pending']);

        $this->patchJson("/api/admin/vendors/{$vendor->id}/verification", [
            'verification_status' => 'verified',
        ])->assertOk()->assertJsonPath('verification_status', 'verified');

        $vendor->refresh();
        $this->assertSame('verified', $vendor->verification_status);
        $this->assertNotNull($vendor->verified_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $vendor->user_id,
            'type' => 'verification',
        ]);
    }

    public function test_rejecting_without_a_reason_is_rejected(): void
    {
        $vendor = Vendor::factory()->create();

        $this->patchJson("/api/admin/vendors/{$vendor->id}/verification", [
            'verification_status' => 'rejected',
        ])->assertUnprocessable()->assertJsonValidationErrors('verification_notes');
    }

    public function test_rejecting_with_a_reason_stores_it_and_notifies(): void
    {
        $vendor = Vendor::factory()->create();

        $this->patchJson("/api/admin/vendors/{$vendor->id}/verification", [
            'verification_status' => 'rejected',
            'verification_notes' => 'Photo illisible, merci de renvoyer.',
        ])->assertOk()->assertJsonPath('verification_status', 'rejected');

        $this->assertSame('Photo illisible, merci de renvoyer.', $vendor->fresh()->verification_notes);
    }

    public function test_an_unknown_vendor_returns_404(): void
    {
        $this->patchJson('/api/admin/vendors/00000000-0000-0000-0000-000000000000/verification', [
            'verification_status' => 'verified',
        ])->assertNotFound();
    }

    public function test_a_non_admin_cannot_review_kyc(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());
        $vendor = Vendor::factory()->create();

        $this->patchJson("/api/admin/vendors/{$vendor->id}/verification", [
            'verification_status' => 'verified',
        ])->assertForbidden();
    }
}
