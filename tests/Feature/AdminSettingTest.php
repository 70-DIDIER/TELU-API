<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->type('admin')->create());
    }

    public function test_an_admin_can_list_settings(): void
    {
        Setting::set('commission_rate_order', '0.10', 'decimal', 'commerce');
        Setting::set('property_free_quota', '3', 'integer', 'immobilier');

        $this->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_an_admin_can_update_a_setting_value(): void
    {
        Setting::set('commission_rate_order', '0.10', 'decimal', 'commerce');

        $this->patchJson('/api/admin/settings/commission_rate_order', ['value' => '0.12'])
            ->assertOk()
            ->assertJsonPath('value', '0.12');

        $this->assertEquals(0.12, Setting::get('commission_rate_order'));
    }

    public function test_updating_an_unknown_setting_returns_404(): void
    {
        $this->patchJson('/api/admin/settings/unknown_key', ['value' => '1'])
            ->assertNotFound();
    }

    public function test_a_non_admin_cannot_access_settings(): void
    {
        Sanctum::actingAs(User::factory()->type('client')->create());

        $this->getJson('/api/admin/settings')->assertForbidden();
    }
}
