<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Finalise les demandes de suppression de compte dont le délai de grâce
 * (User::DELETION_GRACE_DAYS) est écoulé.
 *
 * Les comptes portent un historique transactionnel (commandes, paiements,
 * réservations, avis) référencé en RESTRICT et conservé pour la comptabilité :
 * on ne peut donc pas supprimer physiquement la ligne. On procède par
 * anonymisation — effacement des données personnelles et neutralisation du
 * compte — ce qui satisfait la suppression du point de vue de l'utilisateur
 * tout en préservant l'intégrité référentielle.
 */
class PurgeDeletedAccounts extends Command
{
    protected $signature = 'accounts:purge-deletions';

    protected $description = 'Anonymise les comptes dont la demande de suppression a dépassé le délai de grâce.';

    public function handle(): int
    {
        $cutoff = now()->subDays(User::DELETION_GRACE_DAYS);

        $users = User::query()
            ->whereNotNull('deletion_requested_at')
            ->where('deletion_requested_at', '<=', $cutoff)
            ->where('status', '!=', 'suspended') // exclut les comptes déjà anonymisés
            ->get();

        if ($users->isEmpty()) {
            $this->info('Aucun compte à purger.');

            return self::SUCCESS;
        }

        foreach ($users as $user) {
            DB::transaction(fn () => $this->anonymize($user));
            $this->line("Compte anonymisé : {$user->id}");
        }

        $this->info("{$users->count()} compte(s) purgé(s).");

        return self::SUCCESS;
    }

    /**
     * Efface les données personnelles et neutralise le compte, en conservant
     * les enregistrements financiers/transactionnels qui le référencent.
     */
    private function anonymize(User $user): void
    {
        // Jetons et données personnelles supprimables sans casser la compta.
        $user->tokens()->delete();
        $user->addresses()->delete();
        $user->appNotifications()->delete();
        $user->sentMessages()->delete();
        $user->receivedMessages()->delete();

        // Profils métier (cascade côté BDD, supprimés explicitement ici).
        $user->vendor()->delete();
        $user->driver()->delete();
        $user->propertyOwner()->delete();
        $user->recruiter()->delete();
        $user->jobSeeker()->delete();

        // Scrub des identifiants directs. phone est unique et non-nullable :
        // on le remplace par une valeur unique dérivée de l'id.
        $user->forceFill([
            'full_name' => 'Compte supprimé',
            'email' => null,
            'phone' => 'deleted-'.$user->id,
            'password' => Hash::make(Str::random(40)),
            'profile_photo' => null,
            'current_latitude' => null,
            'current_longitude' => null,
            'is_verified' => false,
            'phone_verified_at' => null,
            'status' => 'suspended',
            'deletion_reason' => null,
        ])->save();
    }
}
