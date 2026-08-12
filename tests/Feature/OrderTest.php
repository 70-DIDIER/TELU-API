<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\OrderController;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Geo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->type('client')->create();
        $this->vendor = Vendor::factory()->create();
        Sanctum::actingAs($this->client);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create($attributes + [
            'vendor_id' => $this->vendor->id,
            'is_available' => true,
            'stock' => 10,
        ]);
    }

    public function test_placing_an_order_computes_the_total_server_side(): void
    {
        $p1 = $this->product(['price' => 1500]);
        $p2 = $this->product(['price' => 2000]);

        $response = $this->postJson('/api/orders', [
            'vendor_id' => $this->vendor->id,
            'delivery_address' => 'Bè, Lomé',
            'items' => [
                ['product_id' => $p1->id, 'quantity' => 2],
                ['product_id' => $p2->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('status', 'pending');

        $order = Order::first();
        // 1500*2 + 2000*1 = 5000 d'articles + le frais de livraison plancher
        // (aucune coordonnée GPS fournie dans cette requête → repli sur delivery_min_fee).
        $this->assertEquals(OrderController::DEFAULT_MIN_FEE, (float) $order->delivery_fee);
        $this->assertEquals(5000.0 + OrderController::DEFAULT_MIN_FEE, (float) $order->total_amount);
        // Commission plateforme calculée sur le sous-total (hors frais de livraison).
        $this->assertEquals(round(5000.0 * OrderController::DEFAULT_COMMISSION_RATE, 2), (float) $order->commission_amount);
        $this->assertEquals(5000.0 - (float) $order->commission_amount, (float) $order->vendor_net_amount);
        $this->assertSame($this->client->id, $order->customer_id);
        $this->assertDatabaseCount('order_items', 2);

        // The unit price is snapshotted from the product.
        $this->assertDatabaseHas('order_items', [
            'product_id' => $p1->id,
            'quantity' => 2,
        ]);

        // The vendor is notified of the new order.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->vendor->user_id,
            'type' => 'order',
        ]);
    }

    public function test_the_delivery_fee_is_computed_from_the_distance_when_coordinates_are_known(): void
    {
        $this->vendor->update(['latitude' => 6.1725, 'longitude' => 1.2314]);
        $product = $this->product(['price' => 1000]);

        // ~5.06 km away (Bè, Lomé) from the vendor's coordinates.
        $response = $this->postJson('/api/orders', [
            'vendor_id' => $this->vendor->id,
            'delivery_address' => 'Bè, Lomé',
            'delivery_latitude' => 6.13,
            'delivery_longitude' => 1.215,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $order = Order::first();

        // frais = base_fee + distance_km * rate_per_km, borné par [min, max].
        $expectedFee = min(
            max(
                OrderController::DEFAULT_BASE_FEE + $this->distanceKm(6.1725, 1.2314, 6.13, 1.215) * OrderController::DEFAULT_RATE_PER_KM,
                OrderController::DEFAULT_MIN_FEE
            ),
            OrderController::DEFAULT_MAX_FEE
        );

        $this->assertEqualsWithDelta(round($expectedFee, 2), (float) $order->delivery_fee, 0.01);
        $this->assertGreaterThan(OrderController::DEFAULT_MIN_FEE, (float) $order->delivery_fee);
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return Geo::distanceKm($lat1, $lng1, $lat2, $lng2);
    }

    public function test_placing_an_order_does_not_touch_the_stock(): void
    {
        $product = $this->product(['price' => 1000, 'stock' => 10]);

        $this->postJson('/api/orders', [
            'vendor_id' => $this->vendor->id,
            'delivery_address' => 'Bè, Lomé',
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertCreated();

        // Stock is only deducted when the vendor accepts.
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_a_product_from_another_vendor_is_rejected(): void
    {
        $foreign = Product::factory()->create(['is_available' => true, 'stock' => 10]);

        $this->postJson('/api/orders', [
            'vendor_id' => $this->vendor->id,
            'delivery_address' => 'Bè, Lomé',
            'items' => [['product_id' => $foreign->id, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_unavailable_product_is_rejected(): void
    {
        $product = $this->product(['is_available' => false]);

        $this->postJson('/api/orders', [
            'vendor_id' => $this->vendor->id,
            'delivery_address' => 'Bè, Lomé',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');
    }

    public function test_an_out_of_stock_quantity_is_rejected(): void
    {
        $product = $this->product(['stock' => 2]);

        $this->postJson('/api/orders', [
            'vendor_id' => $this->vendor->id,
            'delivery_address' => 'Bè, Lomé',
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_customer_only_sees_their_own_orders(): void
    {
        $mine = Order::factory()->create(['customer_id' => $this->client->id]);
        $foreign = Order::factory()->create();

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);

        $this->getJson("/api/orders/{$foreign->id}")->assertNotFound();
    }
}
