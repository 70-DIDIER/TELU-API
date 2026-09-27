<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jusqu'ici une notification ne portait que type + message : ni le push ni
 * la liste in-app ne pouvaient jamais ouvrir directement la ressource
 * concernée (commande, réservation, candidature...) — tout retombait sur
 * l'écran générique /notifications. `route` est une petite clé fixe
 * résolue côté serveur (le seul endroit qui sait, pour une notification
 * donnée, quel écran a du sens pour SON destinataire — un même événement
 * "commande" n'ouvre pas le même écran chez le client et chez le vendeur) ;
 * `reference_id` est l'identifiant de la ressource à injecter dans cet
 * écran quand il en prend un.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('route')->nullable()->after('message');
            $table->uuid('reference_id')->nullable()->after('route');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['route', 'reference_id']);
        });
    }
};
