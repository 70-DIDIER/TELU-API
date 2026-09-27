<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_public_and_returns_the_seeded_defaults(): void
    {
        $this->getJson('/api/app-version')
            ->assertOk()
            ->assertJson([
                'ios' => ['min_version' => '1.0.0', 'latest_version' => '1.0.0'],
                'android' => ['min_version' => '1.0.0', 'latest_version' => '1.0.0'],
                'message' => null,
            ])
            ->assertJsonPath('android.store_url', 'https://play.google.com/store/apps/details?id=com.duokhorus.telu');
    }

    public function test_an_admin_bumping_the_minimum_version_is_reflected_immediately(): void
    {
        Sanctum::actingAs(User::factory()->type('admin')->create());

        $this->patchJson('/api/admin/settings/min_app_version_android', ['value' => '2.0.0'])
            ->assertOk();

        Setting::flushCache();

        $this->getJson('/api/app-version')
            ->assertOk()
            ->assertJsonPath('android.min_version', '2.0.0');
    }

    public function test_an_empty_update_message_setting_is_returned_as_null(): void
    {
        $this->getJson('/api/app-version')->assertOk()->assertJsonPath('message', null);
    }
}
