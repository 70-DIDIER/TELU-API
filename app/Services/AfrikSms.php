<?php

namespace App\Services;

use App\Support\PhoneNumber;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Client HTTP d'AfrikSMS (passerelle SMS ouest-africaine).
 *
 * Comme App\Services\PayGate, aucune exception réseau ne remonte : les méthodes
 * renvoient un tableau normalisé ['ok' => bool, …] pour que l'appelant décide.
 * Le code métier de succès d'AfrikSMS est 100.
 */
class AfrikSms
{
    /** Code de retour « Success operation ». */
    public const CODE_SUCCESS = 100;

    /**
     * Envoie un SMS à un destinataire.
     *
     * @return array{ok: bool, resource_id?: string|null, code?: int|null, message?: string}
     */
    public function send(string $phone, string $message): array
    {
        $mobile = PhoneNumber::e164($phone);

        if ($mobile === '') {
            return ['ok' => false, 'message' => 'Numéro de téléphone invalide.'];
        }

        // Driver `log` : rien n'est envoyé, le SMS est écrit dans les logs.
        // Pratique pour développer les clients sans consommer de crédits.
        if (config('services.afriksms.driver') === 'log') {
            Log::info("AfrikSMS [log] SMS à {$mobile} : {$message}");

            return ['ok' => true, 'code' => self::CODE_SUCCESS, 'resource_id' => 'LOG-'.Str::random(16)];
        }

        $response = $this->get('/send', [
            'SenderId' => $this->senderId(),
            'Message' => $message,
            'MobileNumbers' => $mobile,
        ]);

        if ($response === null) {
            return ['ok' => false, 'message' => "Le service d'envoi de SMS est injoignable."];
        }

        $code = isset($response['code']) ? (int) $response['code'] : null;

        if ($code !== self::CODE_SUCCESS) {
            Log::warning('AfrikSMS: envoi refusé', [
                'code' => $code,
                'message' => $response['message'] ?? null,
            ]);

            return [
                'ok' => false,
                'code' => $code,
                'message' => $response['message'] ?? "L'envoi du SMS a échoué.",
            ];
        }

        return [
            'ok' => true,
            'code' => $code,
            'resource_id' => $response['resourceId'] ?? null,
        ];
    }

    /**
     * Envoie le même SMS à plusieurs destinataires (max 500).
     *
     * @param  list<string>  $phones
     * @return array{ok: bool, code?: int|null, data?: array<int, mixed>, message?: string}
     */
    public function sendMany(array $phones, string $message): array
    {
        $mobiles = collect($phones)
            ->map(fn ($p) => PhoneNumber::e164($p))
            ->filter()
            ->unique()
            ->take(500);

        if ($mobiles->isEmpty()) {
            return ['ok' => false, 'message' => 'Aucun destinataire valide.'];
        }

        $response = $this->post('/send_multisms', [
            'SenderId' => $this->senderId(),
            'Message' => $message,
            'MobileNumbers' => $mobiles->implode(','),
        ]);

        if ($response === null) {
            return ['ok' => false, 'message' => "Le service d'envoi de SMS est injoignable."];
        }

        $code = isset($response['code']) ? (int) $response['code'] : null;

        return [
            // 101 = au moins un numéro en erreur ; l'envoi global reste parti.
            'ok' => $code === self::CODE_SUCCESS,
            'code' => $code,
            'data' => $response['data'] ?? [],
            'message' => $response['message'] ?? null,
        ];
    }

    /**
     * Solde SMS restant, par pays.
     *
     * @return array{ok: bool, information?: array<int, mixed>, message?: string}
     */
    public function balance(): array
    {
        $response = $this->get('/solde');

        if ($response === null) {
            return ['ok' => false, 'message' => 'Le service SMS est injoignable.'];
        }

        return [
            'ok' => (int) ($response['code'] ?? 0) === self::CODE_SUCCESS,
            'information' => $response['information'] ?? [],
            'message' => $response['message'] ?? null,
        ];
    }

    /**
     * Déclare l'URL de réception des accusés de livraison (une seule par compte).
     * `$method` : 1 = POST, 2 = GET.
     *
     * @return array<string, mixed>|null
     */
    public function registerCallbackUrl(string $notifyUrl, int $method = 1): ?array
    {
        return $this->get('/callback_url', [
            'notifyURL' => $notifyUrl,
            'TypeNotification' => $method,
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    private function get(string $path, array $params = []): ?array
    {
        return $this->call('get', $path, $params);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    private function post(string $path, array $params = []): ?array
    {
        return $this->call('post', $path, $params);
    }

    /**
     * Appel HTTP avec les identifiants injectés. Renvoie le corps décodé, ou null.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>|null
     */
    private function call(string $verb, string $path, array $params): ?array
    {
        $clientId = config('services.afriksms.client_id');
        $apiKey = config('services.afriksms.api_key');

        if (blank($clientId) || blank($apiKey)) {
            Log::error('AfrikSMS: AFRIKSMS_CLIENT_ID / AFRIKSMS_API_KEY absents de la configuration.');

            return null;
        }

        $payload = ['ClientId' => $clientId, 'ApiKey' => $apiKey] + $params;

        try {
            $response = $this->client()->{$verb}($path, $payload);
        } catch (\Throwable $e) {
            Log::error('AfrikSMS: appel HTTP en échec', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::error('AfrikSMS: réponse HTTP en erreur', [
                'path' => $path,
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json() ?? [];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.afriksms.base_url'), '/'))
            ->timeout((int) config('services.afriksms.timeout', 20))
            ->acceptJson();
    }

    /**
     * Le SenderId est limité à 11 caractères par l'opérateur.
     */
    private function senderId(): string
    {
        return substr((string) config('services.afriksms.sender_id', 'TELUBAOBAB'), 0, 11);
    }
}
