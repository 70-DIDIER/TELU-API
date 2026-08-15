<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_a_user_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'full_name' => 'Ama Client',
            'phone' => '90112233',
            'email' => 'ama@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'client',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'full_name', 'user_type'], 'token'])
            ->assertJsonPath('user.user_type', 'client');

        // Stocké en E.164 canonique (228 + 8 chiffres) quel que soit le format
        // saisi — voir App\Support\PhoneNumber::e164().
        $this->assertDatabaseHas('users', ['phone' => '22890112233', 'user_type' => 'client']);
    }

    public function test_register_rejects_a_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '90112233']);

        $this->postJson('/api/auth/register', [
            'full_name' => 'Doublon',
            'phone' => '90112233',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'client',
        ])->assertUnprocessable()->assertJsonValidationErrors('phone');
    }

    public function test_register_rejects_an_invalid_user_type(): void
    {
        $this->postJson('/api/auth/register', [
            'full_name' => 'Intrus',
            'phone' => '90112234',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'admin',
        ])->assertUnprocessable()->assertJsonValidationErrors('user_type');
    }

    public function test_login_accepts_an_email(): void
    {
        $user = User::factory()->create(['email' => 'kof@example.com']);

        $this->postJson('/api/auth/login', [
            'login' => 'kof@example.com',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token']);
    }

    public function test_login_accepts_a_phone(): void
    {
        // Stockage canonique E.164 (228 + 8 chiffres) — la connexion se fait
        // avec le numéro local, normalisé côté serveur avant la recherche.
        $user = User::factory()->create(['phone' => '22891223344']);

        $this->postJson('/api/auth/login', [
            'login' => '91223344',
            'password' => 'password',
        ])->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        User::factory()->create(['phone' => '22891223344']);

        $this->postJson('/api/auth/login', [
            'login' => '91223344',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('login');
    }

    public function test_login_rejects_a_suspended_account(): void
    {
        User::factory()->create(['phone' => '22891223344', 'status' => 'suspended']);

        $this->postJson('/api/auth/login', [
            'login' => '91223344',
            'password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('login');
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_update_me_updates_the_authenticated_users_fields(): void
    {
        $user = User::factory()->create(['full_name' => 'Ancien Nom', 'email' => null]);
        Sanctum::actingAs($user);

        $this->putJson('/api/auth/me', [
            'full_name' => 'Nouveau Nom',
            'email' => 'nouveau@example.com',
        ])->assertOk()
            ->assertJsonPath('full_name', 'Nouveau Nom')
            ->assertJsonPath('email', 'nouveau@example.com');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'full_name' => 'Nouveau Nom']);
    }

    public function test_update_me_rejects_an_email_already_taken_by_another_user(): void
    {
        User::factory()->create(['email' => 'pris@example.com']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/auth/me', ['email' => 'pris@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_update_me_requires_authentication(): void
    {
        $this->putJson('/api/auth/me', ['full_name' => 'X'])->assertUnauthorized();
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
