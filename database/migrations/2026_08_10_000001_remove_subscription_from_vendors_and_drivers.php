<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendors and drivers are monetised by commission on each order/delivery
     * (App\Services\CommerceLedger credits their wallet — App\Concerns\HasWallet),
     * never by subscription: `subscriber_type` no longer accepts `vendor`/`driver`
     * (see 2026_08_07_000006_…), so the `subscription_id` column they had since
     * the very first migration (0001_01_01_000003/4_…) is now dead weight.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignUuid('subscription_id')->nullable()->after('user_id')->constrained('subscriptions')->nullOnDelete();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->foreignUuid('subscription_id')->nullable()->after('user_id')->constrained('subscriptions')->nullOnDelete();
        });
    }
};
