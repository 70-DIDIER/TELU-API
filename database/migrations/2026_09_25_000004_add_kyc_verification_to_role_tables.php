<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KYC : chaque profil métier (vendeur, chauffeur, propriétaire, recruteur)
 * uploade déjà un document d'identité (id_document_url, voir
 * 2026_08_15_000003_add_verification_fields_to_role_tables) mais rien ne
 * suivait jusqu'ici si un admin l'avait examiné. Ajoute le statut de revue
 * et le type de pièce fournie — au Togo la CNI n'est pas universelle (carte
 * d'électeur, carte biométrique...), d'où un type explicite plutôt qu'un
 * type de document supposé fixe.
 */
return new class extends Migration
{
    public function up(): void
    {
        $addColumns = function (Blueprint $table) {
            $table->string('id_document_type')->nullable()->after('id_document_url');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending')->after('id_document_type');
            $table->text('verification_notes')->nullable()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verification_notes');
        };

        Schema::table('vendors', $addColumns);
        Schema::table('drivers', $addColumns);
        Schema::table('property_owners', $addColumns);
        Schema::table('recruiters', $addColumns);
    }

    public function down(): void
    {
        $dropColumns = function (Blueprint $table) {
            $table->dropColumn(['id_document_type', 'verification_status', 'verification_notes', 'verified_at']);
        };

        Schema::table('vendors', $dropColumns);
        Schema::table('drivers', $dropColumns);
        Schema::table('property_owners', $dropColumns);
        Schema::table('recruiters', $dropColumns);
    }
};
