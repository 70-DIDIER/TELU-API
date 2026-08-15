<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\FacebookLoginRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Connexion / inscription via Google et Facebook.
 *
 * Le client mobile fait l'échange OAuth lui-même (expo-auth-session) et
 * n'envoie au serveur que le jeton obtenu — jamais de mot de passe. Le
 * serveur revalide systématiquement ce jeton auprès du fournisseur avant de
 * faire confiance à l'identité qu'il porte (jamais de claims non vérifiés).
 *
 * Les comptes créés ici n'ont ni mot de passe ni téléphone : voir
 * OtpController::sendPhoneLink / verifyPhoneLink pour la complétion, requise
 * côté client avant d'utiliser les fonctionnalités qui exigent un numéro
 * (paiement mobile money, contact livreur/vendeur, etc.).
 */
class SocialAuthController extends Controller
{
    /** @var list<string> */
    private const ALLOWED_USER_TYPES = [
        'client', 'vendor', 'driver', 'property_owner', 'recruiter', 'job_seeker',
    ];

    /**
     * Échange un id_token Google (OpenID Connect) contre une session TELU.
     */
    public function google(GoogleLoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $payload = $this->verifyGoogleIdToken($data['id_token']);

        $user = $this->findOrCreateUser(
            provider: 'google',
            providerId: (string) $payload['sub'],
            email: $payload['email'] ?? null,
            emailVerified: ($payload['email_verified'] ?? 'false') === 'true',
            fullName: $payload['name'] ?? ($payload['email'] ?? 'Utilisateur Google'),
            avatar: $payload['picture'] ?? null,
            userType: $data['user_type'] ?? null,
        );

        return $this->respondWithSession($user, $request->input('device_name', 'api'));
    }

    /**
     * Échange un access_token Facebook contre une session TELU.
     */
    public function facebook(FacebookLoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $accessToken = $data['access_token'];

        $this->verifyFacebookAccessToken($accessToken);
        $profile = $this->fetchFacebookProfile($accessToken);

        $user = $this->findOrCreateUser(
            provider: 'facebook',
            providerId: (string) $profile['id'],
            email: $profile['email'] ?? null,
            // Le Graph API ne renvoie que des adresses déjà confirmées par Facebook.
            emailVerified: isset($profile['email']),
            fullName: $profile['name'] ?? 'Utilisateur Facebook',
            avatar: $profile['picture']['data']['url'] ?? null,
            userType: $data['user_type'] ?? null,
        );

        return $this->respondWithSession($user, $request->input('device_name', 'api'));
    }

    /**
     * Valide l'id_token auprès de Google et renvoie ses claims (sub, email,
     * name, picture, email_verified, aud…).
     *
     * Utilise l'endpoint `tokeninfo` de Google (simple, pas de dépendance
     * supplémentaire). Pour un volume de production élevé, préférer une
     * vérification locale de la signature JWT via les clés publiques JWKS
     * de Google (ex. package `firebase/php-jwt`).
     *
     * @return array<string, mixed>
     */
    private function verifyGoogleIdToken(string $idToken): array
    {
        $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        $payload = $response->json();

        if ($response->failed() || ! is_array($payload) || ! isset($payload['sub'])) {
            throw ValidationException::withMessages([
                'id_token' => ['Jeton Google invalide ou expiré.'],
            ]);
        }

        $allowedClientIds = config('services.google.client_ids');

        if ($allowedClientIds !== [] && ! in_array($payload['aud'] ?? null, $allowedClientIds, true)) {
            throw ValidationException::withMessages([
                'id_token' => ["Ce jeton Google n'a pas été émis pour cette application."],
            ]);
        }

        return $payload;
    }

    /**
     * Vérifie, quand app_id/app_secret sont configurés, que le jeton Facebook
     * a bien été émis pour notre application (endpoint `debug_token`).
     */
    private function verifyFacebookAccessToken(string $accessToken): void
    {
        $appId = config('services.facebook.app_id');
        $appSecret = config('services.facebook.app_secret');

        if (! $appId || ! $appSecret) {
            // Pas de vérification stricte possible sans secret d'app configuré ;
            // fetchFacebookProfile() échoue de toute façon si le jeton est
            // invalide, expiré ou révoqué.
            return;
        }

        $response = Http::timeout(10)->get('https://graph.facebook.com/debug_token', [
            'input_token' => $accessToken,
            'access_token' => $appId.'|'.$appSecret,
        ]);

        $data = $response->json('data', []);

        if (
            $response->failed()
            || ! ($data['is_valid'] ?? false)
            || (string) ($data['app_id'] ?? '') !== (string) $appId
        ) {
            throw ValidationException::withMessages([
                'access_token' => ['Jeton Facebook invalide ou expiré.'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchFacebookProfile(string $accessToken): array
    {
        $response = Http::timeout(10)->get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email,picture',
            'access_token' => $accessToken,
        ]);

        $profile = $response->json();

        if ($response->failed() || ! is_array($profile) || ! isset($profile['id'])) {
            throw ValidationException::withMessages([
                'access_token' => ['Jeton Facebook invalide ou expiré.'],
            ]);
        }

        return $profile;
    }

    /**
     * Retrouve (par provider+provider_id, puis par email déjà vérifié par le
     * fournisseur) ou crée le compte correspondant à cette identité sociale.
     */
    private function findOrCreateUser(
        string $provider,
        string $providerId,
        ?string $email,
        bool $emailVerified,
        string $fullName,
        ?string $avatar,
        ?string $userType,
    ): User {
        $user = User::where('provider', $provider)->where('provider_id', $providerId)->first();

        // Compte déjà inscrit par téléphone/mot de passe avec la même adresse
        // email, confirmée par le fournisseur : on relie plutôt que dupliquer.
        if (! $user && $email && $emailVerified) {
            $existing = User::whereNull('provider')->where('email', $email)->first();
            $existing?->update(['provider' => $provider, 'provider_id' => $providerId]);
            $user = $existing;
        }

        if ($user) {
            return $user;
        }

        return User::create([
            'full_name' => $fullName,
            'email' => $email,
            'email_verified_at' => $emailVerified ? now() : null,
            'phone' => null,
            'password' => null,
            'profile_photo' => $avatar,
            'user_type' => in_array($userType, self::ALLOWED_USER_TYPES, true) ? $userType : 'client',
            'provider' => $provider,
            'provider_id' => $providerId,
            // Reflète la vérification du téléphone (voir OtpController), pas
            // celle de l'email — un compte social démarre donc à false ici.
            'is_verified' => false,
        ]);
    }

    private function respondWithSession(User $user, string $deviceName): JsonResponse
    {
        if ($user->status === 'suspended') {
            throw ValidationException::withMessages([
                'login' => ['Ce compte a été suspendu.'],
            ]);
        }

        if ($user->hasPendingDeletion() && $user->deletionPurgeAt()->isPast()) {
            throw ValidationException::withMessages([
                'login' => ['Ce compte a été supprimé.'],
            ]);
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], $user->wasRecentlyCreated ? 201 : 200);
    }
}
