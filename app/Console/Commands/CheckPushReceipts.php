<?php

namespace App\Console\Commands;

use App\Services\ExpoPush;
use Illuminate\Console\Command;

/**
 * Interroge Expo pour les receipts des tickets "ok" envoyés il y a plus de
 * 15 minutes et journalise les échecs de livraison réels (credentials
 * FCM/APNs invalides, appareil désinstallé…) — un ticket "ok" au moment de
 * l'envoi confirme seulement qu'Expo a accepté la demande, pas que FCM/APNs
 * a livré la notification.
 */
class CheckPushReceipts extends Command
{
    protected $signature = 'push:check-receipts';

    protected $description = 'Vérifie les receipts Expo des notifications push envoyées et journalise les échecs de livraison.';

    public function handle(): int
    {
        ExpoPush::checkReceipts();

        return self::SUCCESS;
    }
}
