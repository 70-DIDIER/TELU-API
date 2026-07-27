<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Cycle de vie des codes OTP envoyés par SMS (AfrikSMS).
 *
 * Deux usages (`purpose`) :
 *  - `registration` : valider un numéro qui n'a pas encore de compte ; la
 *    vérification rend un jeton à présenter à POST /api/auth/register ;
 *  - `verification` : un utilisateur connecté confirme son propre numéro.
 *
 * Le code n'est jamais stocké en clair et n'est jamais renvoyé dans une réponse
 * HTTP : il ne transite que par SMS.
 */
class OtpService
{
    public function __construct(private readonly AfrikSms $sms) {}

    /**
     * Génère un code, l'enregistre et l'envoie par SMS.
     *
     * @return array{ok: bool, otp?: OtpCode, message?: string, retry_after?: int, status?: int}
     */
    public function issue(string $phone, string $purpose, ?string $ip = null): array
    {
        $phone = PhoneNumber::international($phone);

        if ($phone === '') {
            return ['ok' => false, 'status' => 422, 'message' => 'Numéro de téléphone invalide.'];
        }

        $delay = (int) config('otp.resend_delay_seconds');
        $last = OtpCode::where('phone', $phone)->where('purpose', $purpose)->latest()->first();

        if ($last && $last->created_at->diffInSeconds(now()) < $delay) {
            $retryAfter = $delay - (int) $last->created_at->diffInSeconds(now());

            return [
                'ok' => false,
                'status' => 429,
                'retry_after' => $retryAfter,
                'message' => "Un code vient d'être envoyé. Réessayez dans {$retryAfter} secondes.",
            ];
        }

        $sentLastHour = OtpCode::where('phone', $phone)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($sentLastHour >= (int) config('otp.max_per_hour')) {
            return [
                'ok' => false,
                'status' => 429,
                'retry_after' => 3600,
                'message' => 'Trop de codes demandés pour ce numéro. Réessayez dans une heure.',
            ];
        }

        $code = $this->generateCode();
        $ttl = (int) config('otp.ttl_minutes');

        $otp = OtpCode::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl),
            'ip' => $ip,
        ]);

        $result = $this->sms->send($phone, $this->buildMessage($code, $ttl));

        if (! $result['ok']) {
            // Pas de SMS parti : on retire le code pour ne pas bloquer un renvoi.
            $otp->delete();

            return [
                'ok' => false,
                'status' => 502,
                'message' => $result['message'] ?? "Le SMS n'a pas pu être envoyé.",
            ];
        }

        $otp->update(['resource_id' => $result['resource_id'] ?? null]);

        return ['ok' => true, 'otp' => $otp->fresh()];
    }

    /**
     * Vérifie un code. En cas de succès sur `registration`, un jeton à usage
     * unique est généré et renvoyé dans `OtpCode::verification_token`.
     *
     * @return array{ok: bool, otp?: OtpCode, message?: string, attempts_left?: int, status?: int}
     */
    public function verify(string $phone, string $purpose, string $code): array
    {
        $phone = PhoneNumber::international($phone);

        $otp = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Aucun code valide pour ce numéro. Demandez un nouveau code.',
            ];
        }

        $maxAttempts = (int) config('otp.max_attempts');

        if ($otp->attempts >= $maxAttempts) {
            return [
                'ok' => false,
                'status' => 429,
                'message' => 'Trop de tentatives sur ce code. Demandez un nouveau code.',
            ];
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            $left = max(0, $maxAttempts - $otp->attempts);

            return [
                'ok' => false,
                'status' => 422,
                'attempts_left' => $left,
                'message' => "Code incorrect. Il vous reste {$left} tentative(s).",
            ];
        }

        $otp->update([
            'verified_at' => now(),
            'verification_token' => $purpose === 'registration' ? Str::random(64) : null,
            // Une vérification par un utilisateur connecté est immédiatement finale.
            'consumed_at' => $purpose === 'registration' ? null : now(),
        ]);

        return ['ok' => true, 'otp' => $otp->fresh()];
    }

    /**
     * Consomme le jeton remis après vérification, pour le numéro donné.
     * Renvoie false si le jeton est inconnu, expiré ou déjà utilisé.
     */
    public function redeemRegistrationToken(string $phone, ?string $token): bool
    {
        if (blank($token)) {
            return false;
        }

        $otp = OtpCode::where('verification_token', $token)
            ->where('phone', PhoneNumber::international($phone))
            ->where('purpose', 'registration')
            ->whereNotNull('verified_at')
            ->whereNull('consumed_at')
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->verified_at->addMinutes((int) config('otp.token_ttl_minutes'))->isPast()) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    /**
     * Code numérique aléatoire, sans biais (random_int).
     */
    private function generateCode(): string
    {
        $length = max(4, (int) config('otp.length'));
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }

        return $code;
    }

    private function buildMessage(string $code, int $ttlMinutes): string
    {
        return str_replace(
            [':code', ':minutes'],
            [$code, (string) $ttlMinutes],
            (string) config('otp.message'),
        );
    }
}
