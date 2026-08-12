<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vendor_account_can_create_its_vendor_profile(): void
    {
        $user = User::factory()->type('vendor')->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/vendor', ['shop_name' => 'Chez Ama'])
            ->assertCreated()
            ->assertJsonPath('shop_name', 'Chez Ama')
            ->assertJsonPath('user_id', $user->id);
    }

    public function test_user_id_in_the_body_is_ignored_when_creating_a_profile(): void
    {
        $user = User::factory()->type('vendor')->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/vendor', [
            'shop_name' => 'Chez Ama',
            'user_id' => $other->id,
        ])->assertCreated()->assertJsonPath('user_id', $user->id);
    }

    public function test_a_standard_client_account_can_add_a_vendor_profile(): void
    {
        $user = User::factory()->type('client')->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/vendor', ['shop_name' => 'Chez Ama'])
            ->assertCreated()
            ->assertJsonPath('shop_name', 'Chez Ama')
            ->assertJsonPath('user_id', $user->id);
    }

    public function test_an_admin_account_cannot_create_a_vendor_profile(): void
    {
        Sanctum::actingAs(User::factory()->type('admin')->create());

        $this->postJson('/api/vendor', ['shop_name' => 'Chez Ama'])->assertForbidden();
    }

    public function test_a_second_vendor_profile_is_rejected(): void
    {
        $user = User::factory()->type('vendor')->create();
        Vendor::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/vendor', ['shop_name' => 'Doublon'])->assertConflict();
    }

    public function test_show_returns_404_without_a_profile(): void
    {
        Sanctum::actingAs(User::factory()->type('vendor')->create());

        $this->getJson('/api/vendor')->assertNotFound();
    }

    public function test_a_vendor_can_update_their_profile(): void
    {
        $user = User::factory()->type('vendor')->create();
        Vendor::factory()->create(['user_id' => $user->id, 'shop_name' => 'Avant']);
        Sanctum::actingAs($user);

        $this->putJson('/api/vendor', ['shop_name' => 'Après'])
            ->assertOk()
            ->assertJsonPath('shop_name', 'Après');
    }

    public function test_a_driver_account_can_create_its_driver_profile(): void
    {
        Sanctum::actingAs(User::factory()->type('driver')->create());

        $this->postJson('/api/driver', ['vehicle_type' => 'moto'])
            ->assertCreated()
            ->assertJsonPath('vehicle_type', 'moto');
    }

    public function test_a_hotel_owner_profile_requires_a_company_name(): void
    {
        Sanctum::actingAs(User::factory()->type('property_owner')->create());

        $this->postJson('/api/property-owner', ['owner_type' => 'hotel'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('company_name');

        $this->postJson('/api/property-owner', [
            'owner_type' => 'hotel',
            'company_name' => 'Hôtel du Lac',
        ])->assertCreated();
    }

    public function test_a_recruiter_account_can_create_its_recruiter_profile(): void
    {
        Sanctum::actingAs(User::factory()->type('recruiter')->create());

        $this->postJson('/api/recruiter', ['company_name' => 'BTP Togo'])->assertCreated();
    }

    public function test_a_job_seeker_account_can_create_its_job_seeker_profile(): void
    {
        Sanctum::actingAs(User::factory()->type('job_seeker')->create());

        $this->postJson('/api/job-seeker', ['profession' => 'Maçon'])->assertCreated();
    }
}
