<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // PayGate's own cut (2.5% Flooz / 3% TMoney, public tariff), computed from
            // `amount` at success — informational only, never deducted from vendor/driver
            // net amounts (absorbed by the platform margin). Null until settled.
            $table->decimal('paygate_fee_amount', 10, 2)->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('paygate_fee_amount');
        });
    }
};
