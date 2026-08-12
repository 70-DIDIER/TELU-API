<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Distance-based delivery fee computed at order placement (replaces the
            // former flat OrderController::DELIVERY_FEE constant) and reused as-is
            // when VendorOrderController::openDelivery() prices the Delivery row.
            $table->decimal('delivery_fee', 8, 2)->default(0)->after('total_amount');
            // Platform commission on the product subtotal (total_amount - delivery_fee),
            // and what the vendor actually earns net of that commission.
            $table->decimal('commission_amount', 10, 2)->default(0)->after('delivery_fee');
            $table->decimal('vendor_net_amount', 10, 2)->default(0)->after('commission_amount');
            // Set once the vendor/driver wallets have been credited for this order, so
            // CommerceLedger::settleOrderIfReady() never double-credits (idempotency guard).
            $table->timestamp('wallet_settled_at')->nullable()->after('vendor_net_amount');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            // Platform commission on delivery_fee, and what the driver actually earns net.
            $table->decimal('commission_amount', 8, 2)->default(0)->after('delivery_fee');
            $table->decimal('driver_net_amount', 8, 2)->default(0)->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_fee', 'commission_amount', 'vendor_net_amount', 'wallet_settled_at']);
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['commission_amount', 'driver_net_amount']);
        });
    }
};
