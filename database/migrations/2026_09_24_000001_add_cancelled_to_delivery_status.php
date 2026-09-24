<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A delivery opened when the vendor accepted an order must be closed
        // when that order is cancelled, otherwise it stays in the drivers' pool.
        $this->setStatuses(['awaiting_driver', 'assigned', 'picked_up', 'delivered', 'cancelled']);
    }

    public function down(): void
    {
        DB::table('deliveries')->where('status', 'cancelled')->delete();

        $this->setStatuses(['awaiting_driver', 'assigned', 'picked_up', 'delivered']);
    }

    /**
     * Rewrite the deliveries.status enum/check, driver-aware (raw CHECK
     * constraint on Postgres, column rebuild on SQLite).
     *
     * @param  list<string>  $statuses
     */
    private function setStatuses(array $statuses): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $list = collect($statuses)->map(fn ($s) => "'".$s."'")->implode(', ');

            DB::statement('ALTER TABLE deliveries DROP CONSTRAINT deliveries_status_check');
            DB::statement("ALTER TABLE deliveries ADD CONSTRAINT deliveries_status_check CHECK (status::text = ANY (ARRAY[{$list}]::text[]))");

            return;
        }

        Schema::table('deliveries', function (Blueprint $table) use ($statuses) {
            $table->enum('status', $statuses)->default('awaiting_driver')->change();
        });
    }
};
