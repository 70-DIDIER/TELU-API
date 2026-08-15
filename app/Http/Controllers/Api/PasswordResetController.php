<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;

/**
 * Mot de passe oublié, par code OTP envoyé en SMS (AfrikSMS).
 *
 * Parcours en trois temps, calqué sur l'inscription :
 *  1. POST /api/auth/password/forgot  — envoie un code au numéro s'il est
 *     associé à un compte (404 sinon) ;
 *  2. POST /api/auth/password/verify  — vérifie le code et rend un `reset_token`
 *     à usage unique ;
 *  3. POST /api/auth/password/reset   — échange le jeton contre le nouveau mot
 *     de passe et révoque tous les jetons Sanctum de l'utilisateur.
 */
class PasswordResetController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    /**
     * Envoie un code de réinitialisation au numéro fourni.
     */
    public function forgot(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->internationalPhone();

        if (! $this->findUserByPhone($phone)) {
            return response()->json([
                'message' => 'Aucun compte associé à ce numéro.',
            ], 404);
        }

        $result = $this->otp->issue($phone, 'password_reset', $request->ip());

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
     * Vérifie le code reçu et rend le jeton autorisant la réinitialisation.
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->otp->verify($data['phone'], 'password_reset', $data['code']);

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
            'reset_token' => $result['otp']->verification_token,
            'expires_in' => (int) config('otp.token_ttl_minutes') * 60,
        ]);
    }

    /**
     * Échange le jeton contre le nouveau mot de passe.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! $this->otp->redeemToken($data['phone'], $data['reset_token'], 'password_reset')) {
            return response()->json([
                'message' => 'Jeton de réinitialisation invalide ou expiré. Redemandez un code par SMS.',
            ], 422);
        }

        $user = $this->findUserByPhone(PhoneNumber::e164($data['phone']));

        if (! $user) {
            return response()->json(['message' => 'Aucun compte associé à ce numéro.'], 404);
        }

        // Le cast `hashed` du modèle chiffre le mot de passe ; la révocation des
        // jetons déconnecte tous les appareils, y compris un éventuel voleur.
        $user->update(['password' => $data['password']]);
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Mot de passe réinitialisé. Connectez-vous avec votre nouveau mot de passe.',
        ]);
    }

    /**
     * Retrouve le compte associé au numéro, quelle qu'en soit l'écriture.
     */
    private function findUserByPhone(string $internationalPhone): ?User
    {
        $query = User::query()->where('phone', $internationalPhone);

        // Filet de sécurité pour les anciennes lignes togolaises qui auraient
        // pu être enregistrées sous une autre forme (locale à 8 chiffres,
        // avec un "+" superflu) — non pertinent pour un numéro étranger.
        if (str_starts_with($internationalPhone, PhoneNumber::DEFAULT_COUNTRY_CODE)) {
            $query->orWhere('phone', PhoneNumber::local($internationalPhone))
                ->orWhere('phone', '+'.$internationalPhone);
        }

        return $query->first();
    }
}
