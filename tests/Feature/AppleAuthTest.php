<?php

namespace Tests\Feature;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppleAuthTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = 'com.duokhorus.telu';

    /**
     * Clé RSA 2048 bits générée une fois pour ces tests uniquement (aucun
     * usage réel) — statique plutôt que générée à la volée via
     * openssl_pkey_new() car cette dernière dépend d'un openssl.cnf présent
     * sur la machine, absent de certains environnements (ex. XAMPP Windows).
     */
    private const TEST_PRIVATE_KEY = <<<'PEM'
    -----BEGIN PRIVATE KEY-----
    MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQDUfxi5oLuknAxC
    RN4SBJFmbBo2V9AQYrgWwv/48ad36leJPGB6gbvJCgEg+OIAsqneAsbhloS0B52k
    om/rZhBa9ZgwTYniDMubImP/bYgL7bChsAQouJXP1LrsxjO+BaH2IktCmRots5Ka
    t9tS6248Mxp2BfFN0DO97a1nYy9/jjjUJZ0I6DYFQIy8WKN+teZm383hgG7XYCCp
    1uJjmq68zXzQMNfp0eCFxUPN+VxalsKX3hkKUGd386Mm2VJzu690KySE0PyNTfig
    n8W1LovGuFmBWw4QAmdAjqBghX1J2QcCm3afr8VIBTAstTcpX3lWbeDRiSZpgw0k
    uQFSUNJ3AgMBAAECggEACsx8sq5G6uodO17plCvUQcq7mEhlI/gIV+vH+1w4gQER
    OsGtO3yNUP2nqgfL4HIz7LriZYNzMfzzF3ND0cgpi53Qgp/mIm05CaS5RTlJQSXu
    pIDf7TvYLSwn5bF63qzFms8KWROTv3/RMvil9jRNsVR9g0LxDN3IS14hQlBJkSdO
    6jfXXlowcachX9Ou1TMxZiQseB6ilc3p6UHxeYEoNe/5pAZt179OmC0iVFwxuvKX
    haqRMn5pgaL5ggam4wkZhqEyUWDrExpKE5W4+GvQ3ub9OUsY0axg9RyL0I74X8np
    gnks4R3z+4k1oEsVYL650wLGsvKdhDUYRiUVul/AsQKBgQDqn32fqEx2ULrYOq8V
    n8TzKRy0KT6OrstFRsWaNAbqBNcfja/jZj3MiRHw+aImm+IMbOmX3ntiqJNqwGeN
    Xym7LXcENrU+z6BTMqB8yjxkGus1WlKhsM535l3zFxV3l0aidElyIu9WTeUUbjvQ
    VVnHy9+e6tfm6fzVaPGXvysGUQKBgQDn24LbbSvjE0qhywagNlXKNTl271LFLvYd
    DiR6/3MLskxotnMhKMAiUuMr+Xb8n4MiwjgePagNdCNey1s3cQpIO+MOd3GadUgX
    5E7zlhCIY6yKZmwSEqE6OErRGRsXW07ETO6kWEDoAJsUmGjmoUh78ql9dF6VBkK7
    O080bPJyRwKBgQC7j2qWwqz1fI4Ro8ApskJ4/Om0YLBg0f0v0WbQYj0QwXPUBqmQ
    SUCoDP+pu3ZUFRO9SSfoP3Q1p3vJwCxICMZMmwjk9nMn1kVdnUBM4kMq55YWXbFn
    DvfPQ/rhBRglNWrDHeFE/AaG4Nh736+zWTffj+yhly2nrHBxjmZsH+feMQKBgQCr
    fs8ktHSAynUqhTyKZoZAQewWT+DeHuVGCn7rR2V2IlSoI0O3JCgxezOzBBuBsg7S
    N+xAWgSipuO+qxX2RTOAyGMjATBTOiqwGVxYiggCig9Gc4m+OG9u29JjJXnHZe81
    /V2KzAh+Umxi6HS3Gla973h0Zg3LlsznJnBoa6lM4wKBgQCM/yGtuh/yv7jks6NR
    q0CNnqcyJJVu3oC4f4gGpxvktVjzRD6KACe+XpHBffzA+N7pEP/qHeNiRrnIFlsi
    2NFwuolHx+4D/MzsFb9XQezntZEeQ+Cq9lKIC49ckn7dNYQNEm+fCAaxTD2BkmUR
    T9GojXmB5HCkVqR/8D/Oj4VRvw==
    -----END PRIVATE KEY-----
    PEM;

    /**
     * Fait passer la clé publique correspondant à TEST_PRIVATE_KEY pour le
     * JWKS d'Apple (mocké) : permet de signer un identity_token qui passera
     * la vérification locale de SocialAuthController::apple() sans dépendre
     * d'un vrai jeton Apple.
     *
     * @return array{0: string, 1: string} [private_key_pem, kid]
     */
    private function fakeAppleJwks(): array
    {
        $privateKeyPem = self::TEST_PRIVATE_KEY;
        $details = openssl_pkey_get_details(openssl_pkey_get_private($privateKeyPem));

        $kid = 'test-key-1';
        $base64url = fn (string $bin) => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');

        Http::fake([
            'https://appleid.apple.com/auth/keys' => Http::response([
                'keys' => [[
                    'kty' => 'RSA',
                    'kid' => $kid,
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n' => $base64url($details['rsa']['n']),
                    'e' => $base64url($details['rsa']['e']),
                ]],
            ]),
        ]);

        return [$privateKeyPem, $kid];
    }

    private function signAppleToken(string $privateKeyPem, string $kid, array $claims): string
    {
        return JWT::encode($claims, $privateKeyPem, 'RS256', $kid);
    }

    public function test_apple_login_creates_a_user_and_returns_a_token(): void
    {
        [$privateKey, $kid] = $this->fakeAppleJwks();
        config(['services.apple.client_ids' => [self::CLIENT_ID]]);

        $token = $this->signAppleToken($privateKey, $kid, [
            'iss' => 'https://appleid.apple.com',
            'aud' => self::CLIENT_ID,
            'exp' => time() + 600,
            'iat' => time(),
            'sub' => 'apple-sub-123',
            'email' => 'client@privaterelay.appleid.com',
            'email_verified' => 'true',
        ]);

        $response = $this->postJson('/api/auth/social/apple', [
            'identity_token' => $token,
            'full_name' => 'Jean Test',
            'user_type' => 'client',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'full_name', 'user_type'], 'token'])
            ->assertJsonPath('user.full_name', 'Jean Test')
            ->assertJsonPath('user.user_type', 'client');

        $this->assertDatabaseHas('users', [
            'provider' => 'apple',
            'provider_id' => 'apple-sub-123',
            'email' => 'client@privaterelay.appleid.com',
        ]);
    }

    public function test_apple_login_reuses_the_same_account_on_a_second_login(): void
    {
        [$privateKey, $kid] = $this->fakeAppleJwks();
        config(['services.apple.client_ids' => [self::CLIENT_ID]]);

        $claims = [
            'iss' => 'https://appleid.apple.com',
            'aud' => self::CLIENT_ID,
            'exp' => time() + 600,
            'iat' => time(),
            'sub' => 'apple-sub-456',
            'email' => 'repeat@example.com',
            'email_verified' => 'true',
        ];

        $first = $this->postJson('/api/auth/social/apple', [
            'identity_token' => $this->signAppleToken($privateKey, $kid, $claims),
        ]);
        $first->assertCreated();

        // Apple ne renvoie plus fullName après la première autorisation.
        $second = $this->postJson('/api/auth/social/apple', [
            'identity_token' => $this->signAppleToken($privateKey, $kid, $claims),
        ]);
        $second->assertOk();

        $this->assertSame($first->json('user.id'), $second->json('user.id'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_apple_login_rejects_a_token_signed_for_another_app(): void
    {
        [$privateKey, $kid] = $this->fakeAppleJwks();
        config(['services.apple.client_ids' => [self::CLIENT_ID]]);

        $token = $this->signAppleToken($privateKey, $kid, [
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.someone.else',
            'exp' => time() + 600,
            'iat' => time(),
            'sub' => 'apple-sub-789',
        ]);

        $this->postJson('/api/auth/social/apple', ['identity_token' => $token])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identity_token');
    }

    public function test_apple_login_rejects_a_malformed_token(): void
    {
        $this->fakeAppleJwks();
        config(['services.apple.client_ids' => [self::CLIENT_ID]]);

        $this->postJson('/api/auth/social/apple', ['identity_token' => 'not-a-jwt'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identity_token');
    }

    public function test_apple_login_links_to_an_existing_account_with_the_same_verified_email(): void
    {
        [$privateKey, $kid] = $this->fakeAppleJwks();
        config(['services.apple.client_ids' => [self::CLIENT_ID]]);

        $existing = User::factory()->create([
            'email' => 'linked@example.com',
            'provider' => null,
            'provider_id' => null,
        ]);

        $token = $this->signAppleToken($privateKey, $kid, [
            'iss' => 'https://appleid.apple.com',
            'aud' => self::CLIENT_ID,
            'exp' => time() + 600,
            'iat' => time(),
            'sub' => 'apple-sub-link',
            'email' => 'linked@example.com',
            'email_verified' => 'true',
        ]);

        $response = $this->postJson('/api/auth/social/apple', ['identity_token' => $token]);

        $response->assertOk()->assertJsonPath('user.id', $existing->id);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $existing->id,
            'provider' => 'apple',
            'provider_id' => 'apple-sub-link',
        ]);
    }

    /**
     * Un compte Google existe déjà avec cette adresse email : le lien
     * automatique de findOrCreateUser() ne s'applique qu'aux comptes
     * classiques (whereNull('provider')), donc la contrainte UNIQUE sur
     * `email` serait sinon violée par le create() qui suit — ça doit rester
     * une erreur de validation propre (422), pas un plantage 500.
     */
    public function test_apple_login_fails_cleanly_when_email_already_belongs_to_another_provider(): void
    {
        [$privateKey, $kid] = $this->fakeAppleJwks();
        config(['services.apple.client_ids' => [self::CLIENT_ID]]);

        User::factory()->create([
            'email' => 'shared@example.com',
            'provider' => 'google',
            'provider_id' => 'google-sub-999',
        ]);

        $token = $this->signAppleToken($privateKey, $kid, [
            'iss' => 'https://appleid.apple.com',
            'aud' => self::CLIENT_ID,
            'exp' => time() + 600,
            'iat' => time(),
            'sub' => 'apple-sub-collision',
            'email' => 'shared@example.com',
            'email_verified' => 'true',
        ]);

        $this->postJson('/api/auth/social/apple', ['identity_token' => $token])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
    }
}
