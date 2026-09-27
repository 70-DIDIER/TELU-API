<?php

namespace Tests\Feature;

use App\Models\PushReceipt;
use App\Models\PushToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushReceiptsTest extends TestCase
{
    use RefreshDatabase;

    private function makeReceipt(string $ticketId, ?string $pushTokenId, \DateTimeInterface $createdAt): PushReceipt
    {
        $receipt = PushReceipt::query()->create([
            'push_token_id' => $pushTokenId,
            'ticket_id' => $ticketId,
            'status' => 'pending',
        ]);

        DB::table('push_receipts')->where('id', $receipt->id)->update(['created_at' => $createdAt]);

        return $receipt->fresh();
    }

    public function test_a_receipt_confirmed_ok_by_expo_is_marked_ok(): void
    {
        $token = PushToken::query()->create([
            'user_id' => User::factory()->create()->id,
            'token' => 'ExponentPushToken[a]',
            'platform' => 'android',
        ]);
        $receipt = $this->makeReceipt('ticket-1', $token->id, now()->subMinutes(20));

        Http::fake([
            'exp.host/*' => Http::response(['data' => ['ticket-1' => ['status' => 'ok']]]),
        ]);

        $this->artisan('push:check-receipts')->assertSuccessful();

        $this->assertSame('ok', $receipt->fresh()->status);
        $this->assertNotNull($receipt->fresh()->checked_at);
        $this->assertDatabaseHas('push_tokens', ['id' => $token->id]);
    }

    public function test_a_device_not_registered_receipt_prunes_the_token(): void
    {
        $token = PushToken::query()->create([
            'user_id' => User::factory()->create()->id,
            'token' => 'ExponentPushToken[dead]',
            'platform' => 'ios',
        ]);
        $receipt = $this->makeReceipt('ticket-dead', $token->id, now()->subMinutes(20));

        Http::fake([
            'exp.host/*' => Http::response(['data' => [
                'ticket-dead' => [
                    'status' => 'error',
                    'message' => '"ExponentPushToken[dead]" is not a registered push notification recipient',
                    'details' => ['error' => 'DeviceNotRegistered'],
                ],
            ]]),
        ]);

        $this->artisan('push:check-receipts')->assertSuccessful();

        $this->assertSame('error', $receipt->fresh()->status);
        $this->assertSame('DeviceNotRegistered', $receipt->fresh()->error_code);
        $this->assertDatabaseMissing('push_tokens', ['id' => $token->id]);
    }

    public function test_a_receipt_less_than_15_minutes_old_is_not_checked_yet(): void
    {
        $this->makeReceipt('ticket-fresh', null, now()->subMinutes(5));

        Http::fake();

        $this->artisan('push:check-receipts')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseHas('push_receipts', ['ticket_id' => 'ticket-fresh', 'status' => 'pending']);
    }

    public function test_an_unclaimed_receipt_older_than_two_days_expires_without_a_request(): void
    {
        $this->makeReceipt('ticket-stale', null, now()->subDays(3));

        Http::fake();

        $this->artisan('push:check-receipts')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseHas('push_receipts', ['ticket_id' => 'ticket-stale', 'status' => 'expired']);
    }

    public function test_sending_a_notification_tracks_the_ticket_id_for_later_verification(): void
    {
        Http::fake([
            'exp.host/*' => Http::response(['data' => [['status' => 'ok', 'id' => 'ticket-xyz']]]),
        ]);

        $other = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());
        PushToken::query()->create([
            'user_id' => $other->id,
            'token' => 'ExponentPushToken[receiver]',
            'platform' => 'ios',
        ]);

        $this->postJson('/api/messages', [
            'receiver_id' => $other->id,
            'content' => 'Bonjour !',
        ])->assertCreated();

        $this->assertDatabaseHas('push_receipts', ['ticket_id' => 'ticket-xyz', 'status' => 'pending']);
    }
}
