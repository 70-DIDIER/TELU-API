<?php

namespace App\Services;

use App\Models\PushReceipt;
use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExpoPush
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    private const RECEIPTS_ENDPOINT = 'https://exp.host/--/api/v2/push/getReceipts';

    /** Expo caps a single push request at 100 messages. */
    private const CHUNK_SIZE = 100;

    /** Expo caps a single getReceipts request at 1000 ids. */
    private const RECEIPTS_CHUNK_SIZE = 1000;

    /** Expo recommends waiting at least 15 minutes before a receipt is ready. */
    private const RECEIPT_DELAY_MINUTES = 15;

    /** Unclaimed receipts older than this are dropped instead of retried forever. */
    private const RECEIPT_EXPIRY_DAYS = 2;

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
            // 'high' force une livraison immédiate par FCM même app fermée/Doze ;
            // channelId route vers le canal Android (importance HIGH, son, heads-up)
            // créé côté client dans use-push-notifications.ts — sans lui Android
            // retombe sur un canal "Miscellaneous" muet et sans alerte à l'écran.
            'priority' => 'high',
            'channelId' => 'default',
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
            $token = $tokens->get($index);
            if (! $token) {
                continue;
            }

            if (($ticket['status'] ?? null) === 'error') {
                // Stale/uninstalled device: prune it so future sends stop trying it.
                if (($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
                    $token->delete();
                } else {
                    Log::warning('ExpoPush: ticket en erreur.', [
                        'push_token_id' => $token->id,
                        'message' => $ticket['message'] ?? null,
                        'details' => $ticket['details'] ?? null,
                    ]);
                }

                continue;
            }

            // A ticket "ok" only means Expo accepted the request — it is not a
            // delivery confirmation. Track its id so checkReceipts() can later
            // ask Expo whether FCM/APNs actually delivered it.
            $ticketId = $ticket['id'] ?? null;
            if ($ticketId) {
                PushReceipt::create([
                    'push_token_id' => $token->id,
                    'ticket_id' => $ticketId,
                    'status' => 'pending',
                ]);
            }
        }
    }

    /**
     * Ask Expo for the delivery outcome of pending push tickets and log the
     * real failures (invalid FCM/APNs credentials, uninstalled device, …)
     * that a ticket's immediate "ok" status hides. Meant to run on a
     * schedule (see App\Console\Commands\CheckPushReceipts), never throws.
     */
    public static function checkReceipts(): void
    {
        // Receipts nobody claimed in time: stop tracking them instead of
        // retrying forever (Expo eventually discards old receipts too).
        PushReceipt::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subDays(self::RECEIPT_EXPIRY_DAYS))
            ->update(['status' => 'expired', 'checked_at' => now()]);

        $pending = PushReceipt::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(self::RECEIPT_DELAY_MINUTES))
            ->limit(self::RECEIPTS_CHUNK_SIZE)
            ->get(['id', 'push_token_id', 'ticket_id']);

        if ($pending->isEmpty()) {
            return;
        }

        foreach ($pending->chunk(self::RECEIPTS_CHUNK_SIZE) as $chunk) {
            self::checkReceiptsChunk($chunk);
        }
    }

    /**
     * @param  Collection<int, PushReceipt>  $receipts
     */
    private static function checkReceiptsChunk(Collection $receipts): void
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post(self::RECEIPTS_ENDPOINT, ['ids' => $receipts->pluck('ticket_id')->all()]);
        } catch (Throwable $e) {
            Log::warning('ExpoPush: getReceipts injoignable.', ['error' => $e->getMessage()]);

            return;
        }

        if (! $response->successful()) {
            Log::warning('ExpoPush: getReceipts en erreur.', ['status' => $response->status(), 'body' => $response->body()]);

            return;
        }

        $results = $response->json('data', []);
        $byTicketId = $receipts->keyBy('ticket_id');

        foreach ($results as $ticketId => $result) {
            $receipt = $byTicketId->get($ticketId);
            if (! $receipt) {
                continue;
            }

            if (($result['status'] ?? null) === 'ok') {
                $receipt->update(['status' => 'ok', 'checked_at' => now()]);

                continue;
            }

            $errorCode = $result['details']['error'] ?? null;
            $receipt->update([
                'status' => 'error',
                'error_code' => $errorCode,
                'error_message' => $result['message'] ?? null,
                'checked_at' => now(),
            ]);

            Log::warning('ExpoPush: échec de livraison confirmé par le receipt.', [
                'push_token_id' => $receipt->push_token_id,
                'error_code' => $errorCode,
                'message' => $result['message'] ?? null,
            ]);

            // Confirmed dead device: prune the token so future sends skip it.
            if ($errorCode === 'DeviceNotRegistered' && $receipt->push_token_id) {
                PushToken::destroy($receipt->push_token_id);
            }
        }
    }
}
