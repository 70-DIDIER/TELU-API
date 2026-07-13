<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * List the authenticated user's conversations: one entry per counterpart,
     * with the last message and the number of unread messages received.
     */
    public function conversations(Request $request): JsonResponse
    {
        $me = $request->user()->id;

        $messages = Message::query()
            ->where('sender_id', $me)
            ->orWhere('receiver_id', $me)
            ->latest()
            ->get();

        // Group by counterpart, keeping the newest message first per group.
        $byCounterpart = $messages->groupBy(
            fn (Message $m) => $m->sender_id === $me ? $m->receiver_id : $m->sender_id
        );

        $counterparts = User::query()
            ->whereIn('id', $byCounterpart->keys())
            ->get(['id', 'full_name', 'profile_photo', 'user_type'])
            ->keyBy('id');

        $conversations = $byCounterpart->map(function ($msgs, $counterpartId) use ($me, $counterparts) {
            /** @var Message $last */
            $last = $msgs->first();

            return [
                'counterpart' => $counterparts->get($counterpartId),
                'last_message' => $last,
                'unread_count' => $msgs
                    ->where('receiver_id', $me)
                    ->where('is_read', false)
                    ->count(),
            ];
        })->values();

        return response()->json($conversations);
    }

    /**
     * Show the thread between the authenticated user and another user, newest
     * first. Marks the counterpart's messages to me as read.
     */
    public function thread(Request $request, string $user): JsonResponse
    {
        $me = $request->user()->id;

        if (! User::query()->whereKey($user)->exists()) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        // Mark as read every unread message this user sent me.
        Message::query()
            ->where('sender_id', $user)
            ->where('receiver_id', $me)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $thread = Message::query()
            ->where(fn ($q) => $q->where('sender_id', $me)->where('receiver_id', $user))
            ->orWhere(fn ($q) => $q->where('sender_id', $user)->where('receiver_id', $me))
            ->latest()
            ->paginate(30);

        return response()->json($thread);
    }

    /**
     * Send a message to another user.
     */
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $data = $request->validated();

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $data['receiver_id'],
            'content' => $data['content'],
            'is_read' => false,
        ]);

        // Notify the receiver of the new message.
        Notifier::send(
            $message->receiver_id,
            'message',
            'Vous avez reçu un nouveau message.'
        );

        return response()->json($message, 201);
    }

    /**
     * Total number of unread messages received by the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => Message::query()
                ->where('receiver_id', $request->user()->id)
                ->where('is_read', false)
                ->count(),
        ]);
    }
}
