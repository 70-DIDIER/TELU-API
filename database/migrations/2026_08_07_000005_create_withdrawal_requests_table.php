<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained('wallets');
            $table->decimal('amount', 12, 2);
            // Mobile money number the admin should send the payout to.
            $table->string('phone_number', 20);
            // The wallet is debited immediately on request (funds reserved); a rejection
            // credits it back. No automated PayGate disbursement API exists — an admin
            // sends the mobile money transfer manually, then marks the request `paid`.
            $table->enum('status', ['pending', 'paid', 'rejected'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
