<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give property owners and recruiters — the only two subscriber types
     * (see 2026_08_07_000006_…) — a `subscription_id` reference plus the
     * start/expiry window activated by PaymentController::activateSubscription()
     * on a successful subscription payment. Vendors and drivers never had this
     * lifecycle: their original `subscription_id` column is dropped outright by
     * 2026_08_10_000001_… since they're commission-based, not subscription-based.
     */
    public function up(): void
    {
        Schema::table('property_owners', function (Blueprint $table) {
            $table->foreignUuid('subscription_id')->nullable()->after('owner_type')->constrained('subscriptions')->nullOnDelete();
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_id');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_started_at');
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->foreignUuid('subscription_id')->nullable()->after('user_id')->constrained('subscriptions')->nullOnDelete();
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_id');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('property_owners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropColumn(['subscription_started_at', 'subscription_expires_at']);
        });

        Schema::table('recruiters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropColumn(['subscription_started_at', 'subscription_expires_at']);
        });
    }
};
