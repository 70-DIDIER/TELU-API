<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rater_id')->constrained('users');
            $table->enum('target_type', ['vendor', 'driver', 'property_owner', 'recruiter', 'job_seeker']);
            $table->uuid('target_id');
            $table->tinyInteger('score')->unsigned();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
