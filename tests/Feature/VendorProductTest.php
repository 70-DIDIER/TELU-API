<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorProductTest extends TestCase
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

    public function test_a_user_without_a_vendor_profile_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->getJson('/api/vendor/products')->assertForbidden();
        $this->postJson('/api/vendor/products', ['name' => 'X', 'price' => 100])->assertForbidden();
    }

    public function test_a_vendor_can_create_a_product(): void
    {
        $this->postJson('/api/vendor/products', [
            'name' => 'Riz parfumé 5kg',
            'price' => 4500,
            'stock' => 20,
        ])->assertCreated()
            ->assertJsonPath('name', 'Riz parfumé 5kg')
            ->assertJsonPath('vendor_id', $this->vendor->id);

        $this->assertDatabaseHas('products', [
            'name' => 'Riz parfumé 5kg',
            'vendor_id' => $this->vendor->id,
        ]);
    }

    public function test_the_vendor_listing_only_contains_their_own_products(): void
    {
        $mine = Product::factory()->create(['vendor_id' => $this->vendor->id]);
        Product::factory()->create(); // another vendor

        $this->getJson('/api/vendor/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_another_vendors_product_is_unreachable(): void
    {
        $foreign = Product::factory()->create();

        $this->getJson("/api/vendor/products/{$foreign->id}")->assertNotFound();
        $this->putJson("/api/vendor/products/{$foreign->id}", ['name' => 'Volé'])->assertNotFound();
        $this->deleteJson("/api/vendor/products/{$foreign->id}")->assertNotFound();
    }

    public function test_a_vendor_can_update_their_product(): void
    {
        $product = Product::factory()->create(['vendor_id' => $this->vendor->id, 'price' => 1000]);

        $this->putJson("/api/vendor/products/{$product->id}", ['price' => 1500])
            ->assertOk();

        $this->assertEquals(1500.0, (float) $product->fresh()->price);
    }

    public function test_a_vendor_can_delete_their_product(): void
    {
        $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);

        $this->deleteJson("/api/vendor/products/{$product->id}")->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
