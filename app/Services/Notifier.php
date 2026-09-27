<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class Notifier
{
    /**
     * Send a notification to a single user, both in-app (the `notifications`
     * table) and as a push to every device they've registered.
     *
     * @param  array<string, mixed>  $data  Extra payload merged into the push
     *                                      notification's `data`. `route` and
     *                                      `reference_id`, when present, are
     *                                      also persisted on the notification
     *                                      row itself — the fixed vocabulary a
     *                                      client uses to deep-link straight
     *                                      to the concerned screen (from a
     *                                      push tap or from the in-app list)
     *                                      instead of always landing on the
     *                                      generic notifications screen. The
     *                                      route is resolved here, per call
     *                                      site, because only the caller knows
     *                                      which screen makes sense for THIS
     *                                      recipient (e.g. the same order
     *                                      event opens a different screen for
     *                                      the customer than for the vendor).
     */
    public static function send(string $userId, string $type, string $message, array $data = []): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'route' => $data['route'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
        ]);

        $user = User::find($userId);
        if ($user) {
            ExpoPush::sendToUser($user, 'TELU BAOBAB', $message, ['type' => $type, ...$data]);
        }

        return $notification;
    }

    /**
     * Send the same notification to many users.
     *
     * @param  iterable<string>  $userIds
     * @param  array<string, mixed>  $data
     */
    public static function sendMany(iterable $userIds, string $type, string $message, array $data = []): int
    {
        $count = 0;

        foreach ($userIds as $userId) {
            self::send($userId, $type, $message, $data);
            $count++;
        }

        return $count;
    }
}
