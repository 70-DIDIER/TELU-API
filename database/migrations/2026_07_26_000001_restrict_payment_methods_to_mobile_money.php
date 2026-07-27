<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PayGate Global ne propose que Flooz et TMoney (Mixx by Yas) :
        // les anciennes valeurs sont ramenées sur flooz avant de resserrer l'enum.
        DB::table('payments')
            ->whereIn('payment_method', ['card', 'mobile_money'])
            ->update(['payment_method' => 'flooz']);

        $this->setPaymentMethods(['flooz', 'tmoney']);
    }

    public function down(): void
    {
        $this->setPaymentMethods(['flooz', 'tmoney', 'card', 'mobile_money']);
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
