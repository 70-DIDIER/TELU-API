<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    private User $rater;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rater = User::factory()->type('client')->create();
        $this->vendor = Vendor::factory()->create();
        Sanctum::actingAs($this->rater);
    }

    private function rate(int $score, array $overrides = []): TestResponse
    {
        return $this->postJson('/api/ratings', $overrides + [
            'target_type' => 'vendor',
            'target_id' => $this->vendor->id,
            'score' => $score,
        ]);
    }

    public function test_a_profile_can_be_rated_and_its_owner_is_notified(): void
    {
        $this->rate(4, ['comment' => 'Très bon vendeur.'])
            ->assertCreated()
            ->assertJsonPath('score', 4)
            ->assertJsonPath('rater_id', $this->rater->id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->vendor->user_id,
            'type' => 'rating',
        ]);
    }

    public function test_rating_your_own_profile_is_rejected(): void
    {
        Sanctum::actingAs($this->vendor->user);

        $this->rate(5)->assertUnprocessable();
    }

    public function test_rating_the_same_profile_twice_is_rejected(): void
    {
        $this->rate(4)->assertCreated();
        $this->rate(5)->assertConflict();
    }

    public function test_rating_an_unknown_profile_returns_404(): void
    {
        $this->rate(4, ['target_id' => (string) Str::uuid()])->assertNotFound();
    }

    public function test_the_score_must_be_between_1_and_5(): void
    {
        $this->rate(6)->assertUnprocessable()->assertJsonValidationErrors('score');
    }

    public function test_public_ratings_expose_the_average_and_the_count(): void
    {
        $this->rate(4)->assertCreated();

        Sanctum::actingAs(User::factory()->type('client')->create());
        $this->rate(5)->assertCreated();

        $this->getJson("/api/ratings/vendor/{$this->vendor->id}")
            ->assertOk()
            ->assertJsonPath('average', 4.5)
            ->assertJsonPath('count', 2);
    }

    public function test_an_invalid_target_type_returns_404(): void
    {
        $this->getJson("/api/ratings/banana/{$this->vendor->id}")->assertNotFound();
    }

    public function test_my_ratings_lists_what_i_left(): void
    {
        $this->rate(3)->assertCreated();

        $this->getJson('/api/my-ratings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.rater_id', $this->rater->id);
    }
}
