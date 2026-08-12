<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddressRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    /**
     * List the authenticated user's saved addresses (default first).
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return response()->json($addresses);
    }

    /**
     * Create a saved address for the authenticated user. The very first
     * address is always the default one, regardless of the submitted flag.
     */
    public function store(AddressRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $address = DB::transaction(function () use ($user, $data) {
            $isFirst = ! $user->addresses()->exists();
            $makeDefault = $isFirst || ($data['is_default'] ?? false);

            if ($makeDefault) {
                $user->addresses()->where('is_default', true)->update(['is_default' => false]);
            }

            return $user->addresses()->create([...$data, 'is_default' => $makeDefault]);
        });

        return response()->json($address, 201);
    }

    /**
     * Update one of the authenticated user's addresses.
     */
    public function update(AddressRequest $request, string $address): JsonResponse
    {
        $user = $request->user();
        $found = $user->addresses()->find($address);

        if (! $found) {
            return response()->json(['message' => 'Adresse introuvable.'], 404);
        }

        $data = $request->validated();

        DB::transaction(function () use ($user, $found, $data) {
            if ($data['is_default'] ?? false) {
                $user->addresses()->where('id', '!=', $found->id)->where('is_default', true)->update(['is_default' => false]);
            }

            $found->update($data);
        });

        return response()->json($found->fresh());
    }

    /**
     * Delete one of the authenticated user's addresses. If it was the
     * default, promote the most recent remaining address (if any).
     */
    public function destroy(Request $request, string $address): JsonResponse
    {
        $user = $request->user();
        $found = $user->addresses()->find($address);

        if (! $found) {
            return response()->json(['message' => 'Adresse introuvable.'], 404);
        }

        DB::transaction(function () use ($user, $found) {
            $wasDefault = $found->is_default;
            $found->delete();

            if ($wasDefault) {
                $user->addresses()->latest()->first()?->update(['is_default' => true]);
            }
        });

        return response()->json(['message' => 'Adresse supprimée.']);
    }

    /**
     * Mark one of the authenticated user's addresses as the default one.
     */
    public function setDefault(Request $request, string $address): JsonResponse
    {
        $user = $request->user();
        $found = $user->addresses()->find($address);

        if (! $found) {
            return response()->json(['message' => 'Adresse introuvable.'], 404);
        }

        DB::transaction(function () use ($user, $found) {
            $user->addresses()->where('id', '!=', $found->id)->where('is_default', true)->update(['is_default' => false]);
            $found->update(['is_default' => true]);
        });

        return response()->json($found->fresh());
    }
}
