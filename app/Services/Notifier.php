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
     *                                      notification's `data` (e.g. deep-link info).
     */
    public static function send(string $userId, string $type, string $message, array $data = []): Notification
    {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
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
