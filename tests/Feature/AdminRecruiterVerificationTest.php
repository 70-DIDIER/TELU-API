<?php

namespace Tests\Feature;

use App\Models\Recruiter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminRecruiterVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->type('admin')->create());
    }

    public function test_an_admin_can_verify_a_recruiters_documents(): void
    {
        $recruiter = Recruiter::factory()->create(['verification_status' => 'pending']);

        $this->patchJson("/api/admin/recruiters/{$recruiter->id}/verification", [
            'verification_status' => 'verified',
        ])->assertOk()->assertJsonPath('verification_status', 'verified');

        $this->assertNotNull($recruiter->fresh()->verified_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $recruiter->user_id, 'type' => 'verification']);
    }

    public function test_rejecting_without_a_reason_is_rejected(): void
    {
        $recruiter = Recruiter::factory()->create();

        $this->patchJson("/api/admin/recruiters/{$recruiter->id}/verification", [
            'verification_status' => 'rejected',
        ])->assertUnprocessable()->assertJsonValidationErrors('verification_notes');
    }

    public function test_an_unknown_recruiter_returns_404(): void
    {
        $this->patchJson('/api/admin/recruiters/00000000-0000-0000-0000-000000000000/verification', [
            'verification_status' => 'verified',
        ])->assertNotFound();
    }

    public function test_a_non_admin_cannot_review_kyc(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());
        $recruiter = Recruiter::factory()->create();

        $this->patchJson("/api/admin/recruiters/{$recruiter->id}/verification", [
            'verification_status' => 'verified',
        ])->assertForbidden();
    }
}
