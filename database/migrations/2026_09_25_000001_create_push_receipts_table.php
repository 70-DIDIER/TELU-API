<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('push_token_id')->nullable()->constrained('push_tokens')->nullOnDelete();
            $table->string('ticket_id')->unique();
            $table->enum('status', ['pending', 'ok', 'error', 'expired'])->default('pending');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_receipts');
    }
};
