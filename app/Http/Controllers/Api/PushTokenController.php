<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    /**
     * Register (or reassign) an Expo push token for the authenticated user.
     * A token is unique across the table: re-registering it under a
     * different account (same device, different login) moves it over.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:ios,android'],
        ]);

        $pushToken = PushToken::updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => $request->user()->id, 'platform' => $data['platform']],
        );

        return response()->json($pushToken, 201);
    }

    /**
     * Unregister a device's push token, typically called on logout.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
        ]);

        PushToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $data['token'])
            ->delete();

        return response()->json(['message' => 'Jeton supprimé.']);
    }
}
