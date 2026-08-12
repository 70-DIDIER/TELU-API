<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Subscriptions only ever gate/boost property_owner and recruiter listings
     * (free-quota + "featured" sort — see OwnerPropertyController/
     * RecruiterJobOfferController and Property/JobOffer::getIsFeaturedAttribute()).
     * Vendors and drivers are monetised by commission on each order/delivery
     * instead (App\Services\CommerceLedger, App\Concerns\HasWallet) — they were
     * never meant to be subscribers, so `vendor`/`driver` are replaced outright
     * rather than added alongside them.
     */
    public function up(): void
    {
        $this->setSubscriberTypes(['property_owner', 'recruiter']);
    }

    public function down(): void
    {
        $this->setSubscriberTypes(['vendor', 'driver']);
    }

    /**
     * Rewrite the subscriber_type enum/check to the given set of values,
     * driver-aware (raw CHECK constraint on Postgres, column rebuild on SQLite)
     * — same pattern as 2026_07_13_000001_add_mobile_money_to_payment_method.
     *
     * @param  list<string>  $types
     */
    private function setSubscriberTypes(array $types): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $list = collect($types)->map(fn ($t) => "'".$t."'")->implode(', ');

            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT subscriptions_subscriber_type_check');
            DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_subscriber_type_check CHECK (subscriber_type::text = ANY (ARRAY[{$list}]::text[]))");

            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) use ($types) {
            $table->enum('subscriber_type', $types)->change();
        });
    }
};
