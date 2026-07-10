<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('property_owners')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('property_type', ['room', 'studio', 'apartment', 'house', 'hotel_room']);
            $table->string('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('price_unit', ['night', 'month']);
            $table->integer('bedrooms')->nullable();
            $table->text('image_urls')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
