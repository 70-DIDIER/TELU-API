<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Le driver `log` écrit le SMS dans les logs au lieu de l'envoyer.
        config(['services.afriksms.driver' => 'log']);
    }

    /**
     * An OTP row for the given phone whose plain code is "1234".
     */
    private function otpRow(array $overrides = []): OtpCode
    {
        $otp = new OtpCode;
        $otp->forceFill($overrides + [
            'phone' => '22890112233',
            'purpose' => 'password_reset',
            'code_hash' => Hash::make('1234'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ])->save();

        return $otp->fresh();
    }

    /**
     * An OTP row already verified, carrying a redeemable reset token.
     */
    private function verifiedRow(string $token): OtpCode
    {
        return $this->otpRow([
            'verified_at' => now(),
            'verification_token' => $token,
        ]);
    }

    public function test_forgot_sends_a_code_when_the_phone_has_an_account(): void
    {
        User::factory()->create(['phone' => '90112233']);

        $this->postJson('/api/auth/password/forgot', ['phone' => '+228 90 11 22 33'])
            ->assertOk()
            ->assertJsonStructure(['message', 'expires_at', 'resend_after']);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '22890112233',
            'purpose' => 'password_reset',
        ]);
    }

    public function test_forgot_rejects_a_phone_without_an_account(): void
    {
        $this->postJson('/api/auth/password/forgot', ['phone' => '90 11 22 33'])
            ->assertNotFound();

        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_verify_returns_a_reset_token(): void
    {
        $this->otpRow();

        $this->postJson('/api/auth/password/verify', [
            'phone' => '90112233',
            'code' => '1234',
        ])
            ->assertOk()
            ->assertJsonStructure(['message', 'reset_token', 'expires_in']);
    }

    public function test_verify_rejects_a_wrong_code_and_counts_attempts(): void
    {
        $this->otpRow();

        $this->postJson('/api/auth/password/verify', [
            'phone' => '90112233',
            'code' => '9999',
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'attempts_left']);
    }

    public function test_reset_changes_the_password_and_revokes_all_tokens(): void
    {
        $user = User::factory()->create(['phone' => '90112233', 'password' => 'old-password']);
        $user->createToken('phone-volé');
        $this->verifiedRow('valid-reset-token');

        $this->postJson('/api/auth/password/reset', [
            'phone' => '90112233',
            'reset_token' => 'valid-reset-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());

        // Le jeton est à usage unique.
        $this->postJson('/api/auth/password/reset', [
            'phone' => '90112233',
            'reset_token' => 'valid-reset-token',
            'password' => 'another-password-1',
            'password_confirmation' => 'another-password-1',
        ])->assertStatus(422);
    }

    public function test_reset_rejects_an_unknown_or_expired_token(): void
    {
        User::factory()->create(['phone' => '90112233']);

        $this->postJson('/api/auth/password/reset', [
            'phone' => '90112233',
            'reset_token' => 'unknown-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(422);

        // Jeton vérifié trop vieux (au-delà du TTL du jeton).
        $this->verifiedRow('stale-token')->forceFill([
            'verified_at' => now()->subMinutes((int) config('otp.token_ttl_minutes') + 5),
        ])->save();

        $this->postJson('/api/auth/password/reset', [
            'phone' => '90112233',
            'reset_token' => 'stale-token',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(422);
    }

    public function test_a_registration_token_cannot_reset_a_password(): void
    {
        User::factory()->create(['phone' => '90112233']);

        $token = Str::random(64);
        $this->otpRow([
            'purpose' => 'registration',
            'verified_at' => now(),
            'verification_token' => $token,
        ]);

        $this->postJson('/api/auth/password/reset', [
            'phone' => '90112233',
            'reset_token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(422);
    }
}
