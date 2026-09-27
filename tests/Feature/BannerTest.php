<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_public_and_lists_only_active_banners_in_order(): void
    {
        Banner::factory()->create(['title' => 'Inactive', 'is_active' => false, 'position' => 0]);
        Banner::factory()->create(['title' => 'Second', 'is_active' => true, 'position' => 2]);
        Banner::factory()->create(['title' => 'First', 'is_active' => true, 'position' => 1]);

        $response = $this->getJson('/api/banners')->assertOk();

        $response->assertJsonCount(2);
        $this->assertSame(['First', 'Second'], array_column($response->json(), 'title'));
    }

    public function test_it_only_returns_public_fields(): void
    {
        Banner::factory()->create(['is_active' => true]);

        $this->getJson('/api/banners')
            ->assertOk()
            ->assertJsonStructure([['id', 'image_url', 'link_url', 'title']]);
    }
}
