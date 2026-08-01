<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobSeeker;
use App\Models\Recruiter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JobModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $seekerUser;

    private JobSeeker $seeker;

    private User $recruiterUser;

    private Recruiter $recruiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seekerUser = User::factory()->type('job_seeker')->create();
        $this->seeker = JobSeeker::factory()->create(['user_id' => $this->seekerUser->id]);

        $this->recruiterUser = User::factory()->type('recruiter')->create();
        $this->recruiter = Recruiter::factory()->create(['user_id' => $this->recruiterUser->id]);
    }

    private function offer(array $attributes = []): JobOffer
    {
        return JobOffer::factory()->create($attributes + [
            'recruiter_id' => $this->recruiter->id,
            'is_active' => true,
        ]);
    }

    public function test_the_board_only_lists_active_offers(): void
    {
        Sanctum::actingAs($this->seekerUser);
        $active = $this->offer();
        $inactive = $this->offer(['is_active' => false]);

        $this->getJson('/api/job-offers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);

        $this->getJson("/api/job-offers/{$inactive->id}")->assertNotFound();
    }

    public function test_a_recruiter_can_publish_an_offer(): void
    {
        Sanctum::actingAs($this->recruiterUser);

        $this->postJson('/api/recruiter/job-offers', [
            'title' => 'Maçon pour 3 jours',
            'location' => 'Lomé',
            'daily_rate' => 8000,
            'start_date' => now()->addWeek()->toDateString(),
        ])->assertCreated()->assertJsonPath('recruiter_id', $this->recruiter->id);
    }

    public function test_another_recruiters_offer_is_unreachable(): void
    {
        Sanctum::actingAs($this->recruiterUser);
        $foreign = JobOffer::factory()->create();

        $this->getJson("/api/recruiter/job-offers/{$foreign->id}")->assertNotFound();
        $this->deleteJson("/api/recruiter/job-offers/{$foreign->id}")->assertNotFound();
    }

    public function test_a_seeker_can_apply_to_an_active_offer(): void
    {
        Sanctum::actingAs($this->seekerUser);
        $offer = $this->offer();

        $this->postJson("/api/job-offers/{$offer->id}/apply")
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('job_seeker_id', $this->seeker->id);

        // The recruiter is notified of the application.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->recruiterUser->id,
            'type' => 'job',
        ]);
    }

    public function test_applying_to_an_inactive_offer_is_rejected(): void
    {
        Sanctum::actingAs($this->seekerUser);
        $offer = $this->offer(['is_active' => false]);

        $this->postJson("/api/job-offers/{$offer->id}/apply")->assertNotFound();
    }

    public function test_a_duplicate_application_is_rejected(): void
    {
        Sanctum::actingAs($this->seekerUser);
        $offer = $this->offer();

        $this->postJson("/api/job-offers/{$offer->id}/apply")->assertCreated();
        $this->postJson("/api/job-offers/{$offer->id}/apply")->assertConflict();
    }

    public function test_a_user_without_a_seeker_profile_cannot_apply(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());
        $offer = $this->offer();

        $this->postJson("/api/job-offers/{$offer->id}/apply")->assertForbidden();
    }

    public function test_a_pending_application_can_be_withdrawn(): void
    {
        Sanctum::actingAs($this->seekerUser);
        $application = JobApplication::factory()->create([
            'job_offer_id' => $this->offer()->id,
            'job_seeker_id' => $this->seeker->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/job-seeker/applications/{$application->id}/withdraw")->assertOk();

        $this->assertDatabaseMissing('job_applications', ['id' => $application->id]);
    }

    public function test_an_accepted_application_cannot_be_withdrawn(): void
    {
        Sanctum::actingAs($this->seekerUser);
        $application = JobApplication::factory()->create([
            'job_offer_id' => $this->offer()->id,
            'job_seeker_id' => $this->seeker->id,
            'status' => 'accepted',
        ]);

        $this->postJson("/api/job-seeker/applications/{$application->id}/withdraw")
            ->assertUnprocessable();
    }

    public function test_the_recruiter_lists_applications_on_their_own_offer_only(): void
    {
        Sanctum::actingAs($this->recruiterUser);
        $offer = $this->offer();
        JobApplication::factory()->create(['job_offer_id' => $offer->id, 'status' => 'pending']);

        $this->getJson("/api/recruiter/job-offers/{$offer->id}/applications")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $foreignOffer = JobOffer::factory()->create();
        $this->getJson("/api/recruiter/job-offers/{$foreignOffer->id}/applications")
            ->assertNotFound();
    }

    public function test_the_recruiter_can_accept_then_complete_an_application(): void
    {
        Sanctum::actingAs($this->recruiterUser);
        $application = JobApplication::factory()->create([
            'job_offer_id' => $this->offer()->id,
            'job_seeker_id' => $this->seeker->id,
            'status' => 'pending',
        ]);

        $this->patchJson("/api/recruiter/applications/{$application->id}/status", [
            'status' => 'accepted',
        ])->assertOk()->assertJsonPath('status', 'accepted');

        // The seeker is notified of the decision.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->seekerUser->id,
            'type' => 'job',
        ]);

        $this->patchJson("/api/recruiter/applications/{$application->id}/status", [
            'status' => 'completed',
        ])->assertOk()->assertJsonPath('status', 'completed');
    }

    public function test_an_illegal_application_transition_is_rejected(): void
    {
        Sanctum::actingAs($this->recruiterUser);
        $application = JobApplication::factory()->create([
            'job_offer_id' => $this->offer()->id,
            'status' => 'pending',
        ]);

        // pending → completed skips the acceptance step.
        $this->patchJson("/api/recruiter/applications/{$application->id}/status", [
            'status' => 'completed',
        ])->assertUnprocessable();
    }

    public function test_an_application_on_another_recruiters_offer_is_unreachable(): void
    {
        Sanctum::actingAs($this->recruiterUser);
        $foreign = JobApplication::factory()->create(['status' => 'pending']);

        $this->patchJson("/api/recruiter/applications/{$foreign->id}/status", [
            'status' => 'accepted',
        ])->assertNotFound();
    }
}
