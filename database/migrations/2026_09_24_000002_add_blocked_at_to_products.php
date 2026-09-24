<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Set when an admin takes the product down (moderation). Unlike
            // is_available, the vendor cannot lift it — only an admin can.
            $table->timestamp('blocked_at')->nullable()->after('is_available');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('blocked_at');
        });
    }
};
