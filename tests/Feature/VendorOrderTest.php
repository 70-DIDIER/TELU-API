<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->type('vendor')->create();
        $this->vendor = Vendor::factory()->create(['user_id' => $this->user->id]);
        Sanctum::actingAs($this->user);
    }

    /**
     * A pending order on this vendor with one line of $quantity × $product.
     */
    private function pendingOrder(Product $product, int $quantity = 2): Order
    {
        $order = Order::factory()->create([
            'vendor_id' => $this->vendor->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $product->price,
        ]);

        return $order;
    }

    private function product(int $stock = 10): Product
    {
        return Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'is_available' => true,
            'stock' => $stock,
            'price' => 1000,
        ]);
    }

    public function test_accepting_an_order_deducts_stock_and_opens_a_delivery(): void
    {
        $availableDriver = Driver::factory()->create(['is_available' => true]);
        $busyDriver = Driver::factory()->create(['is_available' => false]);

        $product = $this->product(stock: 10);
        $order = $this->pendingOrder($product, quantity: 2);

        $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('status', 'accepted');

        $this->assertSame(8, $product->fresh()->stock);

        $this->assertDatabaseHas('deliveries', [
            'order_id' => $order->id,
            'status' => 'awaiting_driver',
        ]);

        // Only available drivers are notified of the new delivery.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $availableDriver->user_id,
            'type' => 'delivery',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $busyDriver->user_id,
            'type' => 'delivery',
        ]);
    }

    public function test_accepting_is_refused_when_the_stock_ran_out(): void
    {
        $product = $this->product(stock: 1);
        $order = $this->pendingOrder($product, quantity: 2);

        $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => 'accepted'])
            ->assertUnprocessable();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_an_illegal_transition_is_rejected(): void
    {
        $order = $this->pendingOrder($this->product());

        // pending → preparing skips the accepted step.
        $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => 'preparing'])
            ->assertUnprocessable();

        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_cancelling_an_accepted_order_restores_the_stock(): void
    {
        $product = $this->product(stock: 10);
        $order = $this->pendingOrder($product, quantity: 3);

        $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => 'accepted'])->assertOk();
        $this->assertSame(7, $product->fresh()->stock);

        $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => 'cancelled'])->assertOk();
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_cancelling_a_pending_order_leaves_the_stock_untouched(): void
    {
        $product = $this->product(stock: 10);
        $order = $this->pendingOrder($product, quantity: 3);

        $this->patchJson("/api/vendor/orders/{$order->id}/status", ['status' => 'cancelled'])->assertOk();

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_another_vendors_order_is_unreachable(): void
    {
        $foreign = Order::factory()->create(['status' => 'pending']);

        $this->getJson("/api/vendor/orders/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/vendor/orders/{$foreign->id}/status", ['status' => 'accepted'])
            ->assertNotFound();
    }

    public function test_a_user_without_a_vendor_profile_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->getJson('/api/vendor/orders')->assertForbidden();
    }

    public function test_the_order_list_can_be_filtered_by_status(): void
    {
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'pending']);
        Order::factory()->create(['vendor_id' => $this->vendor->id, 'status' => 'delivered']);

        $this->getJson('/api/vendor/orders?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }
}
