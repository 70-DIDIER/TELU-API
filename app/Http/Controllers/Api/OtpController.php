<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vérification d'un numéro de téléphone par code OTP envoyé en SMS (AfrikSMS).
 *
 * Deux parcours :
 *  - public (`purpose = registration`) : avant de créer un compte. La
 *    vérification rend un `verification_token` à passer à /api/auth/register.
 *  - authentifié (`purpose = verification`) : l'utilisateur confirme le numéro
 *    déjà enregistré sur son compte (`is_verified` + `phone_verified_at`).
 */
class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    /**
     * Envoie un code au numéro fourni, avant inscription.
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->internationalPhone();

        if ($this->phoneIsTaken($phone)) {
            return response()->json([
                'message' => 'Ce numéro est déjà associé à un compte. Connectez-vous.',
            ], 409);
        }

        $result = $this->otp->issue($phone, 'registration', $request->ip());

        return $this->respondToIssue($result);
    }

    /**
     * Vérifie le code reçu et rend le jeton à présenter à l'inscription.
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->otp->verify($data['phone'], 'registration', $data['code']);

        if (! $result['ok']) {
            return response()->json(
                array_filter([
                    'message' => $result['message'],
                    'attempts_left' => $result['attempts_left'] ?? null,
                ], fn ($v) => $v !== null),
                $result['status'] ?? 422
            );
        }

        return response()->json([
            'message' => 'Numéro vérifié.',
            'verification_token' => $result['otp']->verification_token,
            'expires_in' => (int) config('otp.token_ttl_minutes') * 60,
        ]);
    }

    /**
     * Envoie un code au numéro de l'utilisateur connecté.
     */
    public function sendForMe(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->phone_verified_at) {
            return response()->json(['message' => 'Votre numéro est déjà vérifié.'], 409);
        }

        $result = $this->otp->issue($user->phone, 'verification', $request->ip());

        return $this->respondToIssue($result);
    }

    /**
     * Vérifie le code et marque le numéro de l'utilisateur connecté comme validé.
     */
    public function verifyForMe(VerifyOtpRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->phone_verified_at) {
            return response()->json(['message' => 'Votre numéro est déjà vérifié.'], 409);
        }

        $result = $this->otp->verify($user->phone, 'verification', $request->validated()['code']);

        if (! $result['ok']) {
            return response()->json(
                array_filter([
                    'message' => $result['message'],
                    'attempts_left' => $result['attempts_left'] ?? null,
                ], fn ($v) => $v !== null),
                $result['status'] ?? 422
            );
        }

        $user->update(['is_verified' => true, 'phone_verified_at' => now()]);

        return response()->json([
            'message' => 'Numéro vérifié.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Réponse commune aux deux points d'envoi (succès, quota, panne SMS).
     *
     * @param  array<string, mixed>  $result
     */
    private function respondToIssue(array $result): JsonResponse
    {
        if (! $result['ok']) {
            $response = response()->json(
                array_filter([
                    'message' => $result['message'],
                    'retry_after' => $result['retry_after'] ?? null,
                ], fn ($v) => $v !== null),
                $result['status'] ?? 422
            );

            if (isset($result['retry_after'])) {
                $response->header('Retry-After', (string) $result['retry_after']);
            }

            return $response;
        }

        return response()->json([
            'message' => 'Code envoyé par SMS.',
            'expires_at' => $result['otp']->expires_at,
            'resend_after' => (int) config('otp.resend_delay_seconds'),
        ]);
    }

    /**
     * Un compte existe-t-il déjà pour ce numéro, quelle qu'en soit l'écriture ?
     */
    private function phoneIsTaken(string $internationalPhone): bool
    {
        $local = PhoneNumber::local($internationalPhone);

        return User::query()
            ->where('phone', $internationalPhone)
            ->orWhere('phone', $local)
            ->orWhere('phone', '+'.$internationalPhone)
            ->exists();
    }
}
