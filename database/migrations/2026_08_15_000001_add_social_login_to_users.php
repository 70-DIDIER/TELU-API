<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Connexion sociale (Google / Facebook) : `provider` + `provider_id`
     * identifient le compte chez le fournisseur (ex. `google` / sub Google).
     *
     * Un compte créé par ce biais n'a ni mot de passe ni numéro de téléphone
     * tant que l'utilisateur n'a pas complété son profil (voir
     * OtpController::sendPhoneLink / verifyPhoneLink) — `phone` et `password`
     * doivent donc pouvoir être NULL. Plusieurs lignes avec `phone` NULL (ou
     * `provider`/`provider_id` NULL) restent compatibles avec les contraintes
     * UNIQUE existantes : NULL n'est jamais considéré égal à NULL en SQL.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('user_type');
            $table->string('provider_id')->nullable()->after('provider');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->string('password')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['provider', 'provider_id']);
            $table->dropColumn(['provider', 'provider_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
