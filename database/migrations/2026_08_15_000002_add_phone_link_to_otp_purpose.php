<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `phone_link` : un utilisateur déjà connecté (typiquement un compte créé
     * par connexion sociale, sans téléphone) associe un numéro à son compte.
     * Voir OtpController::sendPhoneLink / verifyPhoneLink.
     */
    public function up(): void
    {
        $this->setPurposes(['registration', 'verification', 'password_reset', 'phone_link']);
    }

    public function down(): void
    {
        DB::table('otp_codes')->where('purpose', 'phone_link')->delete();

        $this->setPurposes(['registration', 'verification', 'password_reset']);
    }

    /**
     * Rewrite the otp_codes.purpose enum/check to the given set of values,
     * driver-aware (raw CHECK constraint on Postgres, column rebuild on SQLite).
     *
     * @param  list<string>  $purposes
     */
    private function setPurposes(array $purposes): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $list = collect($purposes)->map(fn ($p) => "'".$p."'")->implode(', ');

            DB::statement('ALTER TABLE otp_codes DROP CONSTRAINT otp_codes_purpose_check');
            DB::statement("ALTER TABLE otp_codes ADD CONSTRAINT otp_codes_purpose_check CHECK (purpose::text = ANY (ARRAY[{$list}]::text[]))");

            return;
        }

        Schema::table('otp_codes', function (Blueprint $table) use ($purposes) {
            $table->enum('purpose', $purposes)->change();
        });
    }
};
