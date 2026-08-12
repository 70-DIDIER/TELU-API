<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Client HTTP de PayGate Global (mobile money togolais : Flooz et TMoney/Mixx by Yas).
 *
 * Toutes les méthodes renvoient un tableau normalisé et ne lèvent pas d'exception
 * réseau : en cas d'échec on renvoie ['ok' => false, 'message' => …] pour que le
 * contrôleur puisse répondre proprement.
 */
class PayGate
{
    /** Réseaux acceptés par PayGate, indexés par `payments.payment_method`. */
    public const NETWORKS = [
        'flooz' => 'FLOOZ',
        'tmoney' => 'TMONEY',
    ];

    /**
     * Détail du dernier échec de `post()` (message d'erreur brut renvoyé par
     * PayGate, ou message d'exception réseau) — évite de renvoyer un
     * "injoignable" générique quand la vraie cause est connue (ex : payload
     * invalide) et donc masquer un bug de notre côté derrière une fausse
     * panne réseau.
     */
    private ?string $lastError = null;

    /** Codes d'état renvoyés par /api/v1/pay. */
    private const PAY_ERRORS = [
        2 => "Jeton d'authentification PayGate invalide.",
        4 => 'Paramètres de paiement invalides.',
        6 => 'Une transaction avec le même identifiant existe déjà.',
    ];

    /**
     * Traduit un code d'état de paiement PayGate en statut applicatif.
     * 0 : payé — 2 : en cours — 4 : expiré — 6 : annulé.
     */
    public static function mapStatus(?int $code): ?string
    {
        return match ($code) {
            0 => 'success',
            2 => 'pending',
            4, 6 => 'failed',
            default => null,
        };
    }

    /**
     * Réseau PayGate correspondant à une méthode de paiement locale.
     */
    public static function network(string $paymentMethod): string
    {
        return self::NETWORKS[$paymentMethod]
            ?? throw new RuntimeException("Méthode de paiement non supportée : {$paymentMethod}");
    }

    /**
     * Initie un paiement (méthode 1 : push USSD sur le téléphone du client).
     *
     * @return array{ok: bool, tx_reference?: string|null, status?: int|null, message?: string}
     */
    public function requestPayment(
        string $phoneNumber,
        int $amount,
        string $identifier,
        string $paymentMethod,
        ?string $description = null,
    ): array {
        $response = $this->post('/api/v1/pay', [
            'phone_number' => $phoneNumber,
            // PayGate exige un montant entier (le XOF n'a pas de sous-unité) : un
            // float PHP même "rond" (5000.0) se sérialise en JSON avec un point
            // décimal et se fait rejeter en 400 ("no decimals").
            'amount' => $amount,
            'identifier' => $identifier,
            'network' => self::network($paymentMethod),
            'description' => $description,
        ]);

        if ($response === null) {
            return ['ok' => false, 'message' => $this->lastError ?? 'Le service de paiement est injoignable.'];
        }

        $status = isset($response['status']) ? (int) $response['status'] : null;

        if ($status !== 0) {
            Log::warning('PayGate: échec de l\'initiation du paiement', [
                'identifier' => $identifier,
                'status' => $status,
            ]);

            return [
                'ok' => false,
                'status' => $status,
                'message' => self::PAY_ERRORS[$status] ?? 'Paiement refusé par PayGate.',
            ];
        }

        return [
            'ok' => true,
            'status' => $status,
            'tx_reference' => $response['tx_reference'] ?? null,
        ];
    }

    /**
     * Lien de la page de paiement hébergée (méthode 2), en repli si le push échoue.
     */
    public function paymentPageUrl(int $amount, string $identifier, ?string $description = null, ?string $phone = null): string
    {
        $query = array_filter([
            'token' => $this->apiKey(),
            'amount' => $amount,
            'identifier' => $identifier,
            'description' => $description,
            'phone' => $phone,
            'url' => config('services.paygate.callback_url'),
        ], fn ($value) => $value !== null && $value !== '');

        return rtrim((string) config('services.paygate.base_url'), '/').'/v1/page?'.http_build_query($query);
    }

    /**
     * Vérifie l'état d'une transaction via la référence PayGate (/api/v1/status).
     *
     * @return array{ok: bool, status?: int|null, payment_status?: string|null, payment_reference?: string|null, datetime?: string|null, payment_method?: string|null, message?: string}
     */
    public function statusByReference(string $txReference): array
    {
        return $this->readStatus('/api/v1/status', ['tx_reference' => $txReference]);
    }

    /**
     * Vérifie l'état d'une transaction via notre identifiant interne (/api/v2/status).
     *
     * @return array{ok: bool, status?: int|null, payment_status?: string|null, payment_reference?: string|null, datetime?: string|null, payment_method?: string|null, message?: string}
     */
    public function statusByIdentifier(string $identifier): array
    {
        return $this->readStatus('/api/v2/status', ['identifier' => $identifier]);
    }

    /**
     * Solde des comptes marchands Flooz et TMoney (IP whitelistée requise).
     *
     * @return array{ok: bool, flooz?: mixed, tmoney?: mixed, message?: string}
     */
    public function balance(): array
    {
        $response = $this->post('/api/v1/check-balance');

        if ($response === null) {
            return ['ok' => false, 'message' => $this->lastError ?? 'Le service de paiement est injoignable.'];
        }

        return [
            'ok' => true,
            'flooz' => $response['flooz'] ?? null,
            'tmoney' => $response['tmoney'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, status?: int|null, payment_status?: string|null, payment_reference?: string|null, datetime?: string|null, payment_method?: string|null, message?: string}
     */
    private function readStatus(string $path, array $payload): array
    {
        $response = $this->post($path, $payload);

        if ($response === null) {
            return ['ok' => false, 'message' => $this->lastError ?? 'Le service de paiement est injoignable.'];
        }

        $status = isset($response['status']) ? (int) $response['status'] : null;

        return [
            'ok' => true,
            'status' => $status,
            'payment_status' => self::mapStatus($status),
            'tx_reference' => $response['tx_reference'] ?? null,
            'payment_reference' => $response['payment_reference'] ?? null,
            'datetime' => $response['datetime'] ?? null,
            'payment_method' => $response['payment_method'] ?? null,
        ];
    }

    /**
     * Appel POST JSON authentifié. Renvoie le corps décodé, ou null sur erreur.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function post(string $path, array $payload = []): ?array
    {
        $this->lastError = null;

        try {
            $response = $this->client()->post($path, array_filter(
                ['auth_token' => $this->apiKey()] + $payload,
                fn ($value) => $value !== null,
            ));
        } catch (\Throwable $e) {
            Log::error('PayGate: appel HTTP en échec', ['path' => $path, 'error' => $e->getMessage()]);
            $this->lastError = 'Le service de paiement est injoignable.';

            return null;
        }

        if ($response->failed()) {
            Log::error('PayGate: réponse HTTP en erreur', [
                'path' => $path,
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);

            // PayGate renvoie parfois du texte brut (pas du JSON) sur les erreurs
            // 4xx de payload — on le remonte tel quel plutôt que de masquer un
            // vrai bug applicatif derrière un faux message "injoignable".
            $json = $response->json();
            $this->lastError = is_array($json) && isset($json['message'])
                ? (string) $json['message']
                : ($response->body() !== '' ? trim($response->body()) : 'Le service de paiement est injoignable.');

            return null;
        }

        return $response->json() ?? [];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.paygate.base_url'), '/'))
            ->timeout((int) config('services.paygate.timeout', 30))
            ->acceptJson()
            ->asJson();
    }

    private function apiKey(): string
    {
        $key = config('services.paygate.api_key');

        if (blank($key)) {
            throw new RuntimeException('PAYGATE_API_KEY est absent de la configuration.');
        }

        return (string) $key;
    }
}
