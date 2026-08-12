<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CommerceLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_full_commerce_flow_credits_vendor_and_driver_wallets_once_paid_and_delivered(): void
    {
        $vendorUser = User::factory()->type('vendor')->create();
        $vendor = Vendor::factory()->create(['user_id' => $vendorUser->id]);
        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'is_available' => true,
            'stock' => 10,
            'price' => 1000,
        ]);

        $driverUser = User::factory()->type('driver')->create();
        $driver = Driver::factory()->create(['user_id' => $driverUser->id, 'is_available' => true]);

        $customer = User::factory()->type('client')->create();
        Sanctum::actingAs($customer);

        $orderId = $this->postJson('/api/orders', [
            'vendor_id' => $vendor->id,
            'delivery_address' => 'Bè, Lomé',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated()->json('id');

        $order = Order::find($orderId);

        Sanctum::actingAs($vendorUser);
        $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => 'accepted'])->assertOk();

        Sanctum::actingAs($driverUser);
        $deliveryId = $this->getJson('/api/driver/deliveries/available')->json('data.0.id');
        $this->postJson("/api/driver/deliveries/{$deliveryId}/claim")->assertOk();
        $this->postJson("/api/driver/deliveries/{$deliveryId}/pickup")->assertOk();

        // The payment already succeeded before the customer confirms receipt.
        Payment::factory()->successful()->create([
            'user_id' => $customer->id,
            'amount' => $order->fresh()->total_amount,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        Sanctum::actingAs($customer);
        $this->postJson("/api/orders/{$order->id}/confirm-receipt")->assertOk();

        $order->refresh();
        $this->assertNotNull($order->wallet_settled_at);

        $delivery = $order->delivery;

        $this->assertEquals((float) $order->vendor_net_amount, (float) $vendor->fresh()->wallet->balance);
        $this->assertEquals((float) $delivery->driver_net_amount, (float) $driver->fresh()->wallet->balance);

        $this->assertDatabaseHas('notifications', ['user_id' => $vendorUser->id, 'type' => 'wallet']);
        $this->assertDatabaseHas('notifications', ['user_id' => $driverUser->id, 'type' => 'wallet']);
    }

    public function test_the_wallet_is_not_credited_without_a_successful_payment(): void
    {
        $vendorUser = User::factory()->type('vendor')->create();
        $vendor = Vendor::factory()->create(['user_id' => $vendorUser->id]);
        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'is_available' => true,
            'stock' => 10,
            'price' => 1000,
        ]);

        $customer = User::factory()->type('client')->create();
        Sanctum::actingAs($customer);

        $orderId = $this->postJson('/api/orders', [
            'vendor_id' => $vendor->id,
            'delivery_address' => 'Bè, Lomé',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated()->json('id');

        $order = Order::find($orderId);

        Sanctum::actingAs($vendorUser);
        $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => 'accepted'])->assertOk();

        $driverUser = User::factory()->type('driver')->create();
        Driver::factory()->create(['user_id' => $driverUser->id, 'is_available' => true]);
        Sanctum::actingAs($driverUser);
        $deliveryId = $this->getJson('/api/driver/deliveries/available')->json('data.0.id');
        $this->postJson("/api/driver/deliveries/{$deliveryId}/claim")->assertOk();
        $this->postJson("/api/driver/deliveries/{$deliveryId}/pickup")->assertOk();

        // No payment was ever made for this order.
        Sanctum::actingAs($customer);
        $this->postJson("/api/orders/{$order->id}/confirm-receipt")->assertOk();

        $this->assertNull($order->fresh()->wallet_settled_at);
        $this->assertNull($vendor->fresh()->wallet);
    }

    public function test_a_payment_succeeding_after_delivery_settles_the_wallets(): void
    {
        Http::fake(['*/api/v1/status' => Http::response(['status' => 0, 'tx_reference' => 'TX-77'])]);
        config(['services.paygate.api_key' => 'test-key']);

        $vendorUser = User::factory()->type('vendor')->create();
        $vendor = Vendor::factory()->create(['user_id' => $vendorUser->id]);

        $driverUser = User::factory()->type('driver')->create();
        $driver = Driver::factory()->create(['user_id' => $driverUser->id]);

        $customer = User::factory()->type('client')->create();

        $order = Order::factory()->create([
            'vendor_id' => $vendor->id,
            'customer_id' => $customer->id,
            'status' => 'delivered',
            'total_amount' => 11000,
            'delivery_fee' => 1000,
            'commission_amount' => 1000,
            'vendor_net_amount' => 9000,
        ]);

        Delivery::factory()->delivered()->create([
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'delivery_fee' => 1000,
            'commission_amount' => 150,
            'driver_net_amount' => 850,
        ]);

        $payment = Payment::factory()->create([
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'user_id' => $customer->id,
            'status' => 'pending',
            'transaction_id' => 'TX-77',
        ]);

        Sanctum::actingAs($customer);
        $this->postJson("/api/payments/{$payment->id}/check")->assertOk();

        $this->assertNotNull($order->fresh()->wallet_settled_at);
        $this->assertEquals(9000.0, (float) $vendor->fresh()->wallet->balance);
        $this->assertEquals(850.0, (float) $driver->fresh()->wallet->balance);
    }

    public function test_settlement_is_idempotent(): void
    {
        $vendor = Vendor::factory()->create();
        $driver = Driver::factory()->create();

        $order = Order::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => 'delivered',
            'vendor_net_amount' => 5000,
        ]);

        Delivery::factory()->delivered()->create([
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'driver_net_amount' => 700,
        ]);

        Payment::factory()->successful()->create([
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        CommerceLedger::settleOrderIfReady($order);
        CommerceLedger::settleOrderIfReady($order->fresh());

        $this->assertEquals(5000.0, (float) $vendor->fresh()->wallet->balance);
        $this->assertEquals(700.0, (float) $driver->fresh()->wallet->balance);
        $this->assertSame(1, $vendor->fresh()->wallet->transactions()->count());
    }

    public function test_a_vendor_can_view_their_wallet(): void
    {
        $vendorUser = User::factory()->type('vendor')->create();
        $vendor = Vendor::factory()->create(['user_id' => $vendorUser->id]);
        $vendor->creditWallet(5000, 'order', null, 'test');

        Sanctum::actingAs($vendorUser);

        $this->getJson('/api/vendor/wallet')
            ->assertOk()
            ->assertJsonPath('balance', '5000.00')
            ->assertJsonCount(1, 'transactions');
    }

    public function test_a_user_without_a_vendor_or_driver_profile_cannot_view_a_wallet(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->getJson('/api/vendor/wallet')->assertForbidden();
    }
}
