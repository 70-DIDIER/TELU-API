<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Numéro au format international sans préfixe (22890112233).
            $table->string('phone', 20);
            $table->enum('purpose', ['registration', 'verification']);
            // Le code n'est jamais stocké en clair.
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            // Jeton remis après vérification, échangé à l'inscription.
            $table->string('verification_token')->nullable()->unique();
            // Identifiant du SMS chez AfrikSMS (suivi des accusés de réception).
            $table->string('resource_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['phone', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
