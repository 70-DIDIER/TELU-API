<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->type('admin')->create());
    }

    public function test_an_admin_can_list_every_banner_active_or_not(): void
    {
        Banner::factory()->create(['is_active' => true]);
        Banner::factory()->create(['is_active' => false]);

        $this->getJson('/api/admin/banners')->assertOk()->assertJsonCount(2);
    }

    public function test_an_admin_can_create_a_banner(): void
    {
        $this->postJson('/api/admin/banners', [
            'image_url' => 'https://cdn.telu.tg/uploads/banners/promo.jpg',
            'link_url' => '/commerce',
            'title' => 'Promo rentrée',
            'position' => 1,
        ])->assertCreated()
            ->assertJsonPath('image_url', 'https://cdn.telu.tg/uploads/banners/promo.jpg')
            ->assertJsonPath('is_active', true);

        $this->assertDatabaseHas('banners', ['title' => 'Promo rentrée']);
    }

    public function test_creating_a_banner_without_an_image_is_rejected(): void
    {
        $this->postJson('/api/admin/banners', ['title' => 'Sans image'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);
    }

    public function test_an_admin_can_update_a_banner(): void
    {
        $banner = Banner::factory()->create(['is_active' => true]);

        $this->putJson("/api/admin/banners/{$banner->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('is_active', false);
    }

    public function test_an_admin_can_delete_a_banner(): void
    {
        $banner = Banner::factory()->create();

        $this->deleteJson("/api/admin/banners/{$banner->id}")->assertOk();

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    }

    public function test_updating_an_unknown_banner_returns_404(): void
    {
        $this->putJson('/api/admin/banners/00000000-0000-0000-0000-000000000000', ['title' => 'x'])
            ->assertNotFound();
    }

    public function test_a_non_admin_cannot_manage_banners(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->getJson('/api/admin/banners')->assertForbidden();
    }
}
