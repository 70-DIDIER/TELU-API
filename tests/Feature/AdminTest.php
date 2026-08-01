<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->type('admin')->create();
    }

    public function test_a_guest_is_rejected(): void
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();
    }

    public function test_a_non_admin_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/stats')->assertForbidden();
    }

    public function test_an_admin_can_list_and_filter_users(): void
    {
        Sanctum::actingAs($this->admin);
        User::factory()->type('vendor')->create();
        User::factory()->type('client')->create();

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(3, 'data'); // + the admin

        $this->getJson('/api/admin/users?user_type=vendor')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_type', 'vendor');
    }

    public function test_an_admin_can_suspend_a_user_and_revoke_their_tokens(): void
    {
        Sanctum::actingAs($this->admin);
        $user = User::factory()->create(['phone' => '90112233']);
        $user->createToken('mobile');

        $this->patchJson("/api/admin/users/{$user->id}/status", ['status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('status', 'suspended');

        $this->assertSame(0, $user->tokens()->count());

        // A suspended user can no longer log in.
        $this->postJson('/api/auth/login', ['login' => '90112233', 'password' => 'password'])
            ->assertUnprocessable();
    }

    public function test_an_admin_cannot_suspend_themselves(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/admin/users/{$this->admin->id}/status", ['status' => 'suspended'])
            ->assertUnprocessable();
    }

    public function test_an_unknown_user_returns_404(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson('/api/admin/users/00000000-0000-0000-0000-000000000000/status', [
            'status' => 'suspended',
        ])->assertNotFound();
    }

    public function test_the_dashboard_stats_are_served(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/stats')->assertOk();
    }
}
