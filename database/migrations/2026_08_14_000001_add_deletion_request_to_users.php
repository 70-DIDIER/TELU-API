<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Demande de suppression du compte (délai de grâce de 30 jours) :
            // horodatage de la demande + motif facultatif. Null = pas de demande.
            $table->timestamp('deletion_requested_at')->nullable()->after('status');
            $table->string('deletion_reason')->nullable()->after('deletion_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['deletion_requested_at', 'deletion_reason']);
        });
    }
};
