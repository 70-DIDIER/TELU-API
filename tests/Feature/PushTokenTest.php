<?php

namespace Tests\Feature;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create();
        Sanctum::actingAs($this->me);
    }

    public function test_a_push_token_can_be_registered(): void
    {
        $this->postJson('/api/push-tokens', [
            'token' => 'ExponentPushToken[abc123]',
            'platform' => 'android',
        ])->assertCreated()
            ->assertJsonPath('user_id', $this->me->id)
            ->assertJsonPath('platform', 'android');

        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $this->me->id,
            'token' => 'ExponentPushToken[abc123]',
        ]);
    }

    public function test_reregistering_a_token_reassigns_it_to_the_new_owner(): void
    {
        $previousOwner = User::factory()->create();
        $token = PushToken::query()->create([
            'user_id' => $previousOwner->id,
            'token' => 'ExponentPushToken[shared]',
            'platform' => 'ios',
        ]);

        $this->postJson('/api/push-tokens', [
            'token' => 'ExponentPushToken[shared]',
            'platform' => 'ios',
        ])->assertCreated();

        $this->assertSame($this->me->id, $token->fresh()->user_id);
        $this->assertSame(1, PushToken::where('token', 'ExponentPushToken[shared]')->count());
    }

    public function test_an_invalid_platform_is_rejected(): void
    {
        $this->postJson('/api/push-tokens', [
            'token' => 'ExponentPushToken[abc123]',
            'platform' => 'windows',
        ])->assertUnprocessable()->assertJsonValidationErrors('platform');
    }

    public function test_a_push_token_can_be_unregistered(): void
    {
        PushToken::query()->create([
            'user_id' => $this->me->id,
            'token' => 'ExponentPushToken[abc123]',
            'platform' => 'android',
        ]);

        $this->deleteJson('/api/push-tokens', ['token' => 'ExponentPushToken[abc123]'])->assertOk();

        $this->assertDatabaseMissing('push_tokens', ['token' => 'ExponentPushToken[abc123]']);
    }

    public function test_sending_a_message_pushes_to_the_receivers_registered_tokens(): void
    {
        Http::fake([
            'exp.host/*' => Http::response(['data' => [['status' => 'ok']]]),
        ]);

        $other = User::factory()->create();
        PushToken::query()->create([
            'user_id' => $other->id,
            'token' => 'ExponentPushToken[receiver]',
            'platform' => 'ios',
        ]);

        $this->postJson('/api/messages', [
            'receiver_id' => $other->id,
            'content' => 'Bonjour !',
        ])->assertCreated();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'exp.host')
            && $request[0]['to'] === 'ExponentPushToken[receiver]');
    }
}
