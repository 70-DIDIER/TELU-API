<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Demande de suppression du compte par l'utilisateur lui-même, avec un délai de
 * grâce (User::DELETION_GRACE_DAYS jours) pendant lequel il peut se reconnecter
 * pour annuler. La suppression définitive est effectuée ensuite par la commande
 * planifiée `accounts:purge-deletions`.
 */
class AccountDeletionController extends Controller
{
    /**
     * Enregistre la demande de suppression et déconnecte l'utilisateur de tous
     * ses appareils. Le compte reste consultable/annulable pendant le délai.
     */
    public function request(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // Idempotent : une nouvelle demande ne réarme pas le compteur du délai.
        if (! $user->hasPendingDeletion()) {
            $user->update([
                'deletion_requested_at' => now(),
                'deletion_reason' => $validated['reason'] ?? null,
            ]);
        }

        // Révoque tous les jetons : l'utilisateur est déconnecté partout.
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Votre demande de suppression a été enregistrée.',
            'deletion_requested_at' => $user->deletion_requested_at,
            'purge_at' => $user->deletionPurgeAt(),
            'grace_days' => User::DELETION_GRACE_DAYS,
        ]);
    }

    /**
     * Annule une demande de suppression encore dans le délai de grâce.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasPendingDeletion()) {
            return response()->json([
                'message' => 'Aucune demande de suppression en cours.',
            ], 422);
        }

        $user->update([
            'deletion_requested_at' => null,
            'deletion_reason' => null,
        ]);

        return response()->json($user);
    }
}
