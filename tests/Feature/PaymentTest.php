<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paygate.api_key' => 'test-key']);

        $this->client = User::factory()->type('client')->create();
        $this->order = Order::factory()->create([
            'customer_id' => $this->client->id,
            'total_amount' => 5000,
        ]);
        Sanctum::actingAs($this->client);
    }

    private function payOrder(array $overrides = []): TestResponse
    {
        return $this->postJson('/api/payments', $overrides + [
            'reference_type' => 'order',
            'reference_id' => $this->order->id,
            'payment_method' => 'flooz',
            'phone_number' => '+228 90 11 22 33',
        ]);
    }

    public function test_a_payment_is_initiated_with_the_server_side_amount(): void
    {
        Http::fake(['*/api/v1/pay' => Http::response(['status' => 0, 'tx_reference' => 'TX-1'])]);

        $this->payOrder()
            ->assertCreated()
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.transaction_id', 'TX-1');

        $payment = Payment::first();
        $this->assertEquals(5000.0, (float) $payment->amount);
        $this->assertStringStartsWith('TELU-', $payment->identifier);
        // The phone is normalised to the 8-digit local form.
        $this->assertSame('90112233', $payment->phone_number);

        // PayGate received our amount, not one from the request body.
        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/api/v1/pay')
                && (float) $request['amount'] === 5000.0
                && $request['network'] === 'FLOOZ';
        });
    }

    public function test_the_amount_cannot_be_forced_from_the_body(): void
    {
        Http::fake(['*/api/v1/pay' => Http::response(['status' => 0, 'tx_reference' => 'TX-1'])]);

        $this->payOrder(['amount' => 1])->assertCreated();

        $this->assertEquals(5000.0, (float) Payment::first()->amount);
    }

    public function test_paying_someone_elses_order_is_forbidden(): void
    {
        $foreign = Order::factory()->create();

        $this->payOrder(['reference_id' => $foreign->id])->assertForbidden();
    }

    public function test_paying_an_unknown_reference_returns_404(): void
    {
        $this->payOrder(['reference_id' => (string) Str::uuid()])->assertNotFound();
    }

    public function test_an_already_paid_reference_is_rejected(): void
    {
        Payment::factory()->successful()->create([
            'user_id' => $this->client->id,
            'reference_type' => 'order',
            'reference_id' => $this->order->id,
        ]);

        $this->payOrder()->assertConflict();
    }

    public function test_a_gateway_refusal_marks_the_payment_failed(): void
    {
        Http::fake(['*/api/v1/pay' => Http::response(['status' => 6])]);

        $this->payOrder()->assertStatus(502);

        $this->assertSame('failed', Payment::first()->status);
    }

    public function test_an_unreachable_gateway_marks_the_payment_failed(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->payOrder()->assertStatus(502);

        $this->assertSame('failed', Payment::first()->status);
    }

    public function test_a_subscription_payment_uses_the_plan_price(): void
    {
        Http::fake(['*/api/v1/pay' => Http::response(['status' => 0, 'tx_reference' => 'TX-2'])]);
        $plan = Subscription::factory()->create(['price' => 12000]);

        $this->postJson('/api/payments', [
            'reference_type' => 'subscription',
            'reference_id' => $plan->id,
            'payment_method' => 'tmoney',
            'phone_number' => '90112233',
        ])->assertCreated();

        $this->assertEquals(12000.0, (float) Payment::first()->amount);
    }

    public function test_check_confirms_a_pending_payment_from_the_gateway(): void
    {
        Http::fake(['*/api/v1/status' => Http::response([
            'status' => 0,
            'tx_reference' => 'TX-1',
            'payment_reference' => 'REF-9',
        ])]);

        $payment = Payment::factory()->create([
            'user_id' => $this->client->id,
            'status' => 'pending',
            'transaction_id' => 'TX-1',
        ]);

        $this->postJson("/api/payments/{$payment->id}/check")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $payment->refresh();
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('REF-9', $payment->payment_reference);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->client->id,
            'type' => 'payment',
        ]);
    }

    public function test_check_does_not_call_the_gateway_for_a_settled_payment(): void
    {
        Http::fake();

        $payment = Payment::factory()->successful()->create(['user_id' => $this->client->id]);

        $this->postJson("/api/payments/{$payment->id}/check")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        Http::assertNothingSent();
    }

    public function test_check_on_someone_elses_payment_returns_404(): void
    {
        $foreign = Payment::factory()->create(['status' => 'pending']);

        $this->postJson("/api/payments/{$foreign->id}/check")->assertNotFound();
    }

    public function test_the_callback_acknowledges_an_unknown_identifier(): void
    {
        $this->postJson('/api/payments/callback', ['identifier' => 'TELU-UNKNOWN'])
            ->assertOk()
            ->assertJsonPath('received', true);
    }

    public function test_the_callback_reconfirms_the_status_with_paygate_before_writing(): void
    {
        Http::fake(['*/api/v2/status' => Http::response([
            'status' => 0,
            'tx_reference' => 'TX-3',
            'payment_reference' => 'REF-3',
        ])]);

        $payment = Payment::factory()->create([
            'user_id' => $this->client->id,
            'status' => 'pending',
            'transaction_id' => null,
            'identifier' => 'TELU-CALLBACK01',
        ]);

        // The webhook is unauthenticated: no Sanctum token here.
        $this->postJson('/api/payments/callback', [
            'identifier' => 'TELU-CALLBACK01',
            'tx_reference' => 'TX-3',
        ])->assertOk();

        $payment->refresh();
        $this->assertSame('success', $payment->status);
        $this->assertSame('TX-3', $payment->transaction_id);
    }

    public function test_the_callback_body_cannot_force_a_success(): void
    {
        // PayGate still reports the transaction as running (code 2).
        Http::fake(['*/api/v2/status' => Http::response(['status' => 2])]);

        $payment = Payment::factory()->create([
            'user_id' => $this->client->id,
            'status' => 'pending',
            'transaction_id' => null,
            'identifier' => 'TELU-CALLBACK02',
        ]);

        $this->postJson('/api/payments/callback', [
            'identifier' => 'TELU-CALLBACK02',
            'payment_reference' => 'FAKE-RECEIPT',
        ])->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_a_user_only_sees_their_own_payments(): void
    {
        Payment::factory()->create(['user_id' => $this->client->id, 'status' => 'pending']);
        Payment::factory()->create(); // someone else's

        $this->getJson('/api/payments')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
