<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->type('client')->create());
    }

    public function test_the_catalogue_only_lists_available_products(): void
    {
        $visible = Product::factory()->create(['is_available' => true]);
        Product::factory()->create(['is_available' => false]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id);
    }

    public function test_the_catalogue_can_be_searched_by_name(): void
    {
        Product::factory()->create(['is_available' => true, 'name' => 'Riz parfumé']);
        Product::factory()->create(['is_available' => true, 'name' => 'Huile rouge']);

        $this->getJson('/api/products?search=Riz')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Riz parfumé');
    }

    public function test_the_catalogue_can_be_filtered_by_category_and_price(): void
    {
        Product::factory()->create(['is_available' => true, 'category' => 'food', 'price' => 1000]);
        Product::factory()->create(['is_available' => true, 'category' => 'food', 'price' => 9000]);
        Product::factory()->create(['is_available' => true, 'category' => 'fashion', 'price' => 2000]);

        $this->getJson('/api/products?category=food&min_price=500&max_price=5000')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'food');
    }

    public function test_the_catalogue_can_be_filtered_by_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $mine = Product::factory()->create(['is_available' => true, 'vendor_id' => $vendor->id]);
        Product::factory()->create(['is_available' => true]);

        $this->getJson("/api/products?vendor_id={$vendor->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_an_available_product_can_be_shown_with_its_vendor(): void
    {
        $product = Product::factory()->create(['is_available' => true]);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('id', $product->id)
            ->assertJsonStructure(['vendor' => ['id', 'shop_name']]);
    }

    public function test_an_unavailable_product_is_not_shown(): void
    {
        $product = Product::factory()->create(['is_available' => false]);

        $this->getJson("/api/products/{$product->id}")->assertNotFound();
    }
}
