<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('id_number')->nullable()->after('description');
            $table->string('id_document_url')->nullable()->after('id_number');
            $table->string('rccm_number')->nullable()->after('id_document_url');
            $table->string('rccm_document_url')->nullable()->after('rccm_number');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('id_document_url')->nullable()->after('license_number');
            $table->string('vehicle_photo_url')->nullable()->after('id_document_url');
        });

        Schema::table('property_owners', function (Blueprint $table) {
            $table->string('id_number')->nullable()->after('company_name');
            $table->string('id_document_url')->nullable()->after('id_number');
            $table->string('ownership_proof_url')->nullable()->after('id_document_url');
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->string('id_number')->nullable()->after('industry');
            $table->string('id_document_url')->nullable()->after('id_number');
            $table->string('rccm_number')->nullable()->after('id_document_url');
            $table->string('company_document_url')->nullable()->after('rccm_number');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['id_number', 'id_document_url', 'rccm_number', 'rccm_document_url']);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['id_document_url', 'vehicle_photo_url']);
        });

        Schema::table('property_owners', function (Blueprint $table) {
            $table->dropColumn(['id_number', 'id_document_url', 'ownership_proof_url']);
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->dropColumn(['id_number', 'id_document_url', 'rccm_number', 'company_document_url']);
        });
    }
};
