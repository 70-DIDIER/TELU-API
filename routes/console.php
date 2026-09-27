<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Finalise chaque jour les demandes de suppression de compte hors délai de grâce.
Schedule::command('accounts:purge-deletions')->dailyAt('03:00');

// Vérifie les receipts Expo des pushs envoyés (délai recommandé de 15 min)
// pour repérer les échecs de livraison silencieux (credentials FCM/APNs
// manquantes, appareil désinstallé…) qu'un ticket "ok" ne révèle pas.
Schedule::command('push:check-receipts')->everyFifteenMinutes();
