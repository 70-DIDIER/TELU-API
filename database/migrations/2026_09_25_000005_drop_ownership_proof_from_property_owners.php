<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le mandat de gestion / justificatif de propriété n'est plus demandé aux
 * propriétaires — seule la pièce d'identité (id_document_url) reste exigée
 * pour le KYC.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_owners', function (Blueprint $table) {
            $table->dropColumn('ownership_proof_url');
        });
    }

    public function down(): void
    {
        Schema::table('property_owners', function (Blueprint $table) {
            $table->string('ownership_proof_url')->nullable()->after('id_document_type');
        });
    }
};
