<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_a_user_only_sees_their_own_notifications(): void
    {
        Notifier::send($this->user->id, 'order', 'Pour moi.');
        Notifier::send(User::factory()->create()->id, 'order', 'Pour un autre.');

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Pour moi.');
    }

    public function test_notifications_can_be_filtered_by_unread_and_type(): void
    {
        $read = Notifier::send($this->user->id, 'order', 'Déjà lue.');
        $read->update(['is_read' => true]);
        Notifier::send($this->user->id, 'delivery', 'Non lue.');

        $this->getJson('/api/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Non lue.');

        $this->getJson('/api/notifications?type=delivery')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'delivery');
    }

    public function test_the_unread_count_is_returned(): void
    {
        Notifier::send($this->user->id, 'order', 'Une.');
        Notifier::send($this->user->id, 'order', 'Deux.');

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 2);
    }

    public function test_a_notification_can_be_marked_as_read(): void
    {
        $notification = Notifier::send($this->user->id, 'order', 'À lire.');

        $this->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('is_read', true);
    }

    public function test_another_users_notification_is_unreachable(): void
    {
        $foreign = Notifier::send(User::factory()->create()->id, 'order', 'Pas à moi.');

        $this->patchJson("/api/notifications/{$foreign->id}/read")->assertNotFound();
    }

    public function test_all_notifications_can_be_marked_read_at_once(): void
    {
        Notifier::send($this->user->id, 'order', 'Une.');
        Notifier::send($this->user->id, 'order', 'Deux.');

        $this->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('marked_read', 2);

        $this->getJson('/api/notifications/unread-count')->assertJsonPath('count', 0);
    }
}
