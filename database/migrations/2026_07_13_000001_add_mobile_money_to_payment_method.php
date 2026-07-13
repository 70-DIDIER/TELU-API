<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->setPaymentMethods(['flooz', 'tmoney', 'card', 'mobile_money']);
    }

    public function down(): void
    {
        $this->setPaymentMethods(['flooz', 'tmoney', 'card']);
    }

    /**
     * Rewrite the payment_method enum/check to the given set of values,
     * driver-aware (raw CHECK constraint on Postgres, column rebuild on SQLite).
     *
     * @param  list<string>  $methods
     */
    private function setPaymentMethods(array $methods): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $list = collect($methods)->map(fn ($m) => "'".$m."'")->implode(', ');

            DB::statement('ALTER TABLE payments DROP CONSTRAINT payments_payment_method_check');
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_payment_method_check CHECK (payment_method::text = ANY (ARRAY[{$list}]::text[]))");

            return;
        }

        Schema::table('payments', function (Blueprint $table) use ($methods) {
            $table->enum('payment_method', $methods)->change();
        });
    }
};
