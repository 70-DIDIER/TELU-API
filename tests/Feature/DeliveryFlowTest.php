<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeliveryFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $driverUser;

    private Driver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driverUser = User::factory()->type('driver')->create();
        $this->driver = Driver::factory()->create([
            'user_id' => $this->driverUser->id,
            'is_available' => true,
        ]);
        Sanctum::actingAs($this->driverUser);
    }

    public function test_the_pool_only_lists_deliveries_awaiting_a_driver(): void
    {
        $open = Delivery::factory()->create();
        Delivery::factory()->assigned()->create();

        $this->getJson('/api/driver/deliveries/available')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $open->id);
    }

    public function test_a_user_without_a_driver_profile_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->getJson('/api/driver/deliveries/available')->assertForbidden();
    }

    public function test_a_driver_can_claim_an_open_delivery(): void
    {
        $delivery = Delivery::factory()->create();

        $this->postJson("/api/driver/deliveries/{$delivery->id}/claim")
            ->assertOk()
            ->assertJsonPath('status', 'assigned')
            ->assertJsonPath('driver_id', $this->driver->id);

        $this->assertNotNull($delivery->fresh()->assigned_at);

        // The vendor is told a courier accepted the delivery.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $delivery->order->vendor->user_id,
            'type' => 'delivery',
        ]);
    }

    public function test_an_already_claimed_delivery_cannot_be_claimed_again(): void
    {
        $delivery = Delivery::factory()->assigned()->create();

        $this->postJson("/api/driver/deliveries/{$delivery->id}/claim")
            ->assertUnprocessable();
    }

    public function test_claiming_an_unknown_delivery_returns_404(): void
    {
        $this->postJson('/api/driver/deliveries/00000000-0000-0000-0000-000000000000/claim')
            ->assertNotFound();
    }

    public function test_pickup_moves_the_order_to_in_delivery_and_notifies_the_customer(): void
    {
        $order = Order::factory()->create(['status' => 'preparing']);
        $delivery = Delivery::factory()->create([
            'order_id' => $order->id,
            'driver_id' => $this->driver->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $this->postJson("/api/driver/deliveries/{$delivery->id}/pickup")
            ->assertOk()
            ->assertJsonPath('status', 'picked_up');

        $this->assertSame('in_delivery', $order->fresh()->status);
        $this->assertNotNull($delivery->fresh()->pickup_time);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $order->customer_id,
            'type' => 'delivery',
        ]);
    }

    public function test_a_driver_cannot_pick_up_another_drivers_delivery(): void
    {
        $foreign = Delivery::factory()->assigned()->create();

        $this->postJson("/api/driver/deliveries/{$foreign->id}/pickup")->assertNotFound();
    }

    public function test_pickup_is_rejected_from_a_wrong_status(): void
    {
        $delivery = Delivery::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'picked_up',
            'assigned_at' => now(),
            'pickup_time' => now(),
        ]);

        $this->postJson("/api/driver/deliveries/{$delivery->id}/pickup")->assertUnprocessable();
    }

    public function test_the_customer_confirms_receipt_and_closes_order_and_delivery(): void
    {
        $customer = User::factory()->type('client')->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => 'in_delivery']);
        $delivery = Delivery::factory()->create([
            'order_id' => $order->id,
            'driver_id' => $this->driver->id,
            'status' => 'picked_up',
            'assigned_at' => now(),
            'pickup_time' => now(),
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/orders/{$order->id}/confirm-receipt")
            ->assertOk()
            ->assertJsonPath('status', 'delivered');

        $delivery->refresh();
        $this->assertSame('delivered', $delivery->status);
        $this->assertNotNull($delivery->delivery_time);

        // Vendor and driver are both notified.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $order->vendor->user_id,
            'type' => 'order',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->driverUser->id,
            'type' => 'delivery',
        ]);
    }

    public function test_the_driver_can_mark_a_picked_up_delivery_as_delivered(): void
    {
        $customer = User::factory()->type('client')->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => 'in_delivery']);
        $delivery = Delivery::factory()->create([
            'order_id' => $order->id,
            'driver_id' => $this->driver->id,
            'status' => 'picked_up',
            'assigned_at' => now(),
            'pickup_time' => now(),
        ]);

        $this->postJson("/api/driver/deliveries/{$delivery->id}/deliver")
            ->assertOk()
            ->assertJsonPath('status', 'delivered');

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertNotNull($delivery->fresh()->delivery_time);

        // Both the customer and the vendor are notified of the delivery.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $order->customer_id,
            'type' => 'delivery',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $order->vendor->user_id,
            'type' => 'order',
        ]);
    }

    public function test_driver_delivery_settles_the_wallets_when_the_order_is_already_paid(): void
    {
        $customer = User::factory()->type('client')->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'in_delivery',
            'vendor_net_amount' => 1800,
        ]);
        $delivery = Delivery::factory()->create([
            'order_id' => $order->id,
            'driver_id' => $this->driver->id,
            'status' => 'picked_up',
            'assigned_at' => now(),
            'pickup_time' => now(),
            'driver_net_amount' => 850,
        ]);

        Payment::factory()->successful()->create([
            'user_id' => $customer->id,
            'amount' => $order->total_amount,
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        $this->postJson("/api/driver/deliveries/{$delivery->id}/deliver")->assertOk();

        $this->assertNotNull($order->fresh()->wallet_settled_at);
        $this->assertEquals(850.0, (float) $this->driver->fresh()->wallet->balance);
    }

    public function test_deliver_is_rejected_from_a_wrong_status(): void
    {
        $delivery = Delivery::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $this->postJson("/api/driver/deliveries/{$delivery->id}/deliver")->assertUnprocessable();
    }

    public function test_a_driver_cannot_deliver_another_drivers_delivery(): void
    {
        $foreign = Delivery::factory()->create([
            'status' => 'picked_up',
            'assigned_at' => now(),
            'pickup_time' => now(),
        ]);

        $this->postJson("/api/driver/deliveries/{$foreign->id}/deliver")->assertNotFound();
    }

    public function test_receipt_cannot_be_confirmed_before_the_delivery_starts(): void
    {
        $customer = User::factory()->type('client')->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => 'accepted']);

        Sanctum::actingAs($customer);

        $this->postJson("/api/orders/{$order->id}/confirm-receipt")->assertUnprocessable();
    }

    public function test_the_full_commerce_flow_from_order_to_confirmed_delivery(): void
    {
        $client = User::factory()->type('client')->create();
        $vendorUser = User::factory()->type('vendor')->create();
        $vendor = Vendor::factory()->create(['user_id' => $vendorUser->id]);
        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'is_available' => true,
            'stock' => 10,
            'price' => 1000,
        ]);

        // 1. The customer places the order.
        Sanctum::actingAs($client);
        $orderId = $this->postJson('/api/orders', [
            'vendor_id' => $vendor->id,
            'delivery_address' => 'Agoè, Lomé',
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertCreated()->json('id');

        // 2. The vendor accepts: stock deducted, delivery opened.
        Sanctum::actingAs($vendorUser);
        $this->patchJson("/api/vendor/orders/{$orderId}/status", ['status' => 'accepted'])->assertOk();
        $this->assertSame(7, $product->fresh()->stock);

        // 3. The driver finds it in the pool and claims it.
        Sanctum::actingAs($this->driverUser);
        $deliveryId = $this->getJson('/api/driver/deliveries/available')
            ->assertOk()
            ->json('data.0.id');
        $this->postJson("/api/driver/deliveries/{$deliveryId}/claim")->assertOk();

        // 4. Pickup: the order goes out for delivery.
        $this->postJson("/api/driver/deliveries/{$deliveryId}/pickup")->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'in_delivery']);

        // 5. The customer confirms receipt: everything is closed.
        Sanctum::actingAs($client);
        $this->postJson("/api/orders/{$orderId}/confirm-receipt")->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'delivered']);
        $this->assertDatabaseHas('deliveries', ['id' => $deliveryId, 'status' => 'delivered']);
    }
}
