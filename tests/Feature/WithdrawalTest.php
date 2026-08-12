<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private User $vendorUser;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorUser = User::factory()->type('vendor')->create();
        $this->vendor = Vendor::factory()->create(['user_id' => $this->vendorUser->id]);
        $this->vendor->creditWallet(10000, 'order', null, 'seed');
        Sanctum::actingAs($this->vendorUser);
    }

    public function test_a_withdrawal_request_debits_the_wallet_immediately(): void
    {
        $this->postJson('/api/withdrawals', ['amount' => 4000, 'phone_number' => '90112233'])
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('amount', '4000.00');

        $this->assertEquals(6000.0, (float) $this->vendor->fresh()->wallet->balance);
    }

    public function test_a_withdrawal_larger_than_the_balance_is_rejected(): void
    {
        $this->postJson('/api/withdrawals', ['amount' => 50000, 'phone_number' => '90112233'])
            ->assertUnprocessable();

        $this->assertEquals(10000.0, (float) $this->vendor->fresh()->wallet->balance);
    }

    public function test_a_user_without_a_wallet_holding_profile_cannot_request_a_withdrawal(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->postJson('/api/withdrawals', ['amount' => 1000, 'phone_number' => '90112233'])
            ->assertForbidden();
    }

    public function test_an_admin_can_mark_a_withdrawal_as_paid(): void
    {
        $this->postJson('/api/withdrawals', ['amount' => 4000, 'phone_number' => '90112233'])->assertCreated();
        $withdrawal = WithdrawalRequest::first();

        Sanctum::actingAs(User::factory()->type('admin')->create());

        $this->patchJson("/api/admin/withdrawals/{$withdrawal->id}/pay")
            ->assertOk()
            ->assertJsonPath('status', 'paid');

        // Marking as paid does not touch the balance again (already debited on request).
        $this->assertEquals(6000.0, (float) $this->vendor->fresh()->wallet->balance);

        $this->assertDatabaseHas('notifications', ['user_id' => $this->vendorUser->id, 'type' => 'wallet']);
    }

    public function test_an_admin_rejecting_a_withdrawal_refunds_the_wallet(): void
    {
        $this->postJson('/api/withdrawals', ['amount' => 4000, 'phone_number' => '90112233'])->assertCreated();
        $withdrawal = WithdrawalRequest::first();
        $this->assertEquals(6000.0, (float) $this->vendor->fresh()->wallet->balance);

        Sanctum::actingAs(User::factory()->type('admin')->create());

        $this->patchJson("/api/admin/withdrawals/{$withdrawal->id}/reject")
            ->assertOk()
            ->assertJsonPath('status', 'rejected');

        $this->assertEquals(10000.0, (float) $this->vendor->fresh()->wallet->balance);
    }

    public function test_an_already_processed_withdrawal_cannot_be_processed_again(): void
    {
        $this->postJson('/api/withdrawals', ['amount' => 4000, 'phone_number' => '90112233'])->assertCreated();
        $withdrawal = WithdrawalRequest::first();

        Sanctum::actingAs(User::factory()->type('admin')->create());
        $this->patchJson("/api/admin/withdrawals/{$withdrawal->id}/pay")->assertOk();
        $this->patchJson("/api/admin/withdrawals/{$withdrawal->id}/pay")->assertUnprocessable();
    }
}
