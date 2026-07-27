<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Identifiant interne unique envoyé à PayGate (paramètre `identifier`).
            $table->string('identifier')->nullable()->unique()->after('transaction_id');
            // Code de référence renvoyé par Flooz / TMoney (`payment_reference`).
            $table->string('payment_reference')->nullable()->after('identifier');
            // Numéro mobile money débité.
            $table->string('phone_number', 20)->nullable()->after('payment_reference');
            $table->timestamp('paid_at')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['identifier']);
            $table->dropColumn(['identifier', 'payment_reference', 'phone_number', 'paid_at']);
        });
    }
};
