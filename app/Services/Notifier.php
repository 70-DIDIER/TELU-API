<?php

namespace App\Services;

use App\Models\Notification;

class Notifier
{
    /**
     * Send a notification to a single user.
     */
    public static function send(string $userId, string $type, string $message): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * Send the same notification to many users.
     *
     * @param  iterable<string>  $userIds
     */
    public static function sendMany(iterable $userIds, string $type, string $message): int
    {
        $count = 0;

        foreach ($userIds as $userId) {
            self::send($userId, $type, $message);
            $count++;
        }

        return $count;
    }
}
