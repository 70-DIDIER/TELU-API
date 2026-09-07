<?php

namespace App\Services;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpoPush
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /** Expo caps a single push request at 100 messages. */
    private const CHUNK_SIZE = 100;

    /**
     * Push a notification to every device registered for a user. Never
     * throws — a gateway/network failure must not break the caller (mirrors
     * App\Services\PayGate / AfrikSms).
     *
     * @param  array<string, mixed>  $data
     */
    public static function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->pushTokens()->get(['id', 'token']);

        if ($tokens->isEmpty()) {
            return;
        }

        foreach ($tokens->chunk(self::CHUNK_SIZE) as $chunk) {
            self::sendChunk($chunk, $title, $body, $data);
        }
    }

    /**
     * @param  Collection<int, PushToken>  $tokens
     * @param  array<string, mixed>  $data
     */
    private static function sendChunk(Collection $tokens, string $title, string $body, array $data): void
    {
        // Reindex 0-based: Expo returns tickets in the same order as the
        // request array, and we map a ticket back to its token by index.
        $tokens = $tokens->values();

        $messages = $tokens->map(fn (PushToken $t) => [
            'to' => $t->token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
        ])->values()->all();

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post(self::ENDPOINT, $messages);
        } catch (Throwable $e) {
            Log::warning('ExpoPush: injoignable.', ['error' => $e->getMessage()]);

            return;
        }

        if (! $response->successful()) {
            Log::warning('ExpoPush: réponse en erreur.', ['status' => $response->status(), 'body' => $response->body()]);

            return;
        }

        $tickets = $response->json('data', []);

        foreach ($tickets as $index => $ticket) {
            if (($ticket['status'] ?? null) !== 'error') {
                continue;
            }

            // Stale/uninstalled device: prune it so future sends stop trying it.
            if (($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
                $tokens->get($index)?->delete();
            }
        }
    }
}
