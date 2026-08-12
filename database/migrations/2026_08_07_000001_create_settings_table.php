<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            // Storage type, used to cast `value` back on read (Setting::get()).
            $table->enum('type', ['string', 'integer', 'decimal', 'boolean'])->default('string');
            // Loose grouping for the backoffice UI (commerce, delivery, immobilier, emploi, paygate...).
            $table->string('group')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
