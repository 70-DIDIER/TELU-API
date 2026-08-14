<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Finalise chaque jour les demandes de suppression de compte hors délai de grâce.
Schedule::command('accounts:purge-deletions')->dailyAt('03:00');
