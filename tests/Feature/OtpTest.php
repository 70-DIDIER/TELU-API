<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OtpTest extends TestCase
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
            'purpose' => 'registration',
            'code_hash' => Hash::make('1234'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ])->save();

        return $otp->fresh();
    }

    public function test_a_code_is_sent_and_stored_hashed(): void
    {
        $this->postJson('/api/auth/otp/send', ['phone' => '90 11 22 33'])
            ->assertOk()
            ->assertJsonStructure(['message', 'expires_at', 'resend_after']);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '22890112233',
            'purpose' => 'registration',
        ]);

        // The code itself never leaves by HTTP.
        $this->assertArrayNotHasKey('code', OtpCode::first()->toArray());
    }

    public function test_a_phone_that_already_has_an_account_is_rejected(): void
    {
        User::factory()->create(['phone' => '90112233']);

        $this->postJson('/api/auth/otp/send', ['phone' => '+228 90 11 22 33'])
            ->assertConflict();
    }

    public function test_resending_too_soon_is_throttled(): void
    {
        $this->postJson('/api/auth/otp/send', ['phone' => '90112233'])->assertOk();

        $this->postJson('/api/auth/otp/send', ['phone' => '90112233'])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_the_hourly_quota_is_enforced(): void
    {
        foreach (range(1, 5) as $i) {
            $this->otpRow(['created_at' => now()->subMinutes(10 + $i)]);
        }

        $this->postJson('/api/auth/otp/send', ['phone' => '90112233'])
            ->assertStatus(429)
            ->assertJsonPath('retry_after', 3600);
    }

    public function test_a_wrong_code_burns_an_attempt(): void
    {
        $this->otpRow();

        $this->postJson('/api/auth/otp/verify', ['phone' => '90112233', 'code' => '9999'])
            ->assertUnprocessable()
            ->assertJsonPath('attempts_left', 4);
    }

    public function test_a_correct_code_returns_a_verification_token(): void
    {
        $this->otpRow();

        $this->postJson('/api/auth/otp/verify', ['phone' => '90112233', 'code' => '1234'])
            ->assertOk()
            ->assertJsonStructure(['verification_token', 'expires_in']);
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $this->otpRow(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/auth/otp/verify', ['phone' => '90112233', 'code' => '1234'])
            ->assertUnprocessable();
    }

    public function test_a_code_with_exhausted_attempts_is_rejected(): void
    {
        $this->otpRow(['attempts' => 5]);

        $this->postJson('/api/auth/otp/verify', ['phone' => '90112233', 'code' => '1234'])
            ->assertStatus(429);
    }

    public function test_registering_with_a_valid_token_verifies_the_phone(): void
    {
        $this->otpRow();
        $token = $this->postJson('/api/auth/otp/verify', ['phone' => '90112233', 'code' => '1234'])
            ->json('verification_token');

        $this->postJson('/api/auth/register', [
            'full_name' => 'Ama Vérifiée',
            'phone' => '90112233',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'client',
            'otp_token' => $token,
        ])->assertCreated()->assertJsonPath('user.is_verified', true);

        $user = User::where('phone', '90112233')->first();
        $this->assertNotNull($user->phone_verified_at);

        // The token is one-shot.
        $this->assertNotNull(OtpCode::first()->consumed_at);
    }

    public function test_registration_requires_the_token_when_the_flag_is_on(): void
    {
        config(['otp.required_for_registration' => true]);

        $this->postJson('/api/auth/register', [
            'full_name' => 'Sans OTP',
            'phone' => '90112233',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => 'client',
        ])->assertUnprocessable()->assertJsonValidationErrors('otp_token');
    }

    public function test_an_authenticated_user_can_verify_their_own_number(): void
    {
        $user = User::factory()->create(['phone' => '90112233', 'phone_verified_at' => null]);
        Sanctum::actingAs($user);

        $this->otpRow(['purpose' => 'verification']);

        $this->postJson('/api/otp/verify', ['code' => '1234'])
            ->assertOk()
            ->assertJsonPath('user.is_verified', true);

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_an_already_verified_number_cannot_request_a_code(): void
    {
        $user = User::factory()->create(['phone' => '90112233', 'phone_verified_at' => now()]);
        Sanctum::actingAs($user);

        $this->postJson('/api/otp/send')->assertConflict();
    }
}
