<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List the authenticated user's notifications, newest first.
     * Optional filters: ?unread=1 (only unread), ?type=order|delivery|reservation|job.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->appNotifications()
            ->when($request->boolean('unread'), fn ($q) => $q->where('is_read', false))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return response()->json($notifications);
    }

    /**
     * Number of unread notifications for the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->appNotifications()->where('is_read', false)->count(),
        ]);
    }

    /**
     * Mark one of the authenticated user's notifications as read.
     */
    public function markRead(Request $request, string $notification): JsonResponse
    {
        $found = $request->user()->appNotifications()->find($notification);

        if (! $found) {
            return response()->json(['message' => 'Notification introuvable.'], 404);
        }

        if (! $found->is_read) {
            $found->update(['is_read' => true]);
        }

        return response()->json($found->fresh());
    }

    /**
     * Mark all of the authenticated user's unread notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $updated = $request->user()
            ->appNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['marked_read' => $updated]);
    }
}
