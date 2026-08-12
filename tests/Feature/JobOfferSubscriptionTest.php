<?php

namespace Tests\Feature;

use App\Models\JobOffer;
use App\Models\Recruiter;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JobOfferSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function recruiter(bool $subscribed = false): Recruiter
    {
        $user = User::factory()->type('recruiter')->create();
        $attributes = ['user_id' => $user->id];

        if ($subscribed) {
            $plan = Subscription::factory()->create(['subscriber_type' => 'recruiter']);
            $attributes += [
                'subscription_id' => $plan->id,
                'subscription_started_at' => now(),
                'subscription_expires_at' => now()->addDays(30),
            ];
        }

        $recruiter = Recruiter::factory()->create($attributes);
        Sanctum::actingAs($user);

        return $recruiter;
    }

    private function offerPayload(): array
    {
        return [
            'title' => 'Maçon pour 3 jours',
            'location' => 'Lomé',
            'daily_rate' => 8000,
            'start_date' => now()->addWeek()->toDateString(),
        ];
    }

    public function test_a_non_subscribed_recruiter_is_blocked_beyond_the_free_quota(): void
    {
        Setting::set('job_offer_free_quota', 2, 'integer');
        $recruiter = $this->recruiter();

        JobOffer::factory()->count(2)->create(['recruiter_id' => $recruiter->id]);

        $this->postJson('/api/recruiter/job-offers', $this->offerPayload())->assertForbidden();
    }

    public function test_a_subscribed_recruiter_publishes_without_limit(): void
    {
        Setting::set('job_offer_free_quota', 1, 'integer');
        $recruiter = $this->recruiter(subscribed: true);

        JobOffer::factory()->count(5)->create(['recruiter_id' => $recruiter->id]);

        $this->postJson('/api/recruiter/job-offers', $this->offerPayload())->assertCreated();
    }

    public function test_subscribed_recruiters_offers_are_featured_first(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $free = $this->recruiter();
        $subscribed = $this->recruiter(subscribed: true);

        $oldFeatured = JobOffer::factory()->create([
            'recruiter_id' => $subscribed->id,
            'is_active' => true,
            'created_at' => now()->subDays(5),
        ]);
        $recentFree = JobOffer::factory()->create([
            'recruiter_id' => $free->id,
            'is_active' => true,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/job-offers')->assertOk();

        $response->assertJsonPath('data.0.id', $oldFeatured->id);
        $response->assertJsonPath('data.0.is_featured', true);
        $response->assertJsonPath('data.1.id', $recentFree->id);
        $response->assertJsonPath('data.1.is_featured', false);
    }
}
