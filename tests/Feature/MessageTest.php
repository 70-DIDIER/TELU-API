<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create();
        $this->other = User::factory()->create();
        Sanctum::actingAs($this->me);
    }

    public function test_a_message_can_be_sent_and_notifies_the_receiver(): void
    {
        $this->postJson('/api/messages', [
            'receiver_id' => $this->other->id,
            'content' => 'Bonjour !',
        ])->assertCreated()
            ->assertJsonPath('sender_id', $this->me->id)
            ->assertJsonPath('is_read', false);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->other->id,
            'type' => 'message',
        ]);
    }

    public function test_messaging_yourself_is_rejected(): void
    {
        $this->postJson('/api/messages', [
            'receiver_id' => $this->me->id,
            'content' => 'Allô moi-même ?',
        ])->assertUnprocessable()->assertJsonValidationErrors('receiver_id');
    }

    public function test_opening_a_thread_marks_incoming_messages_as_read(): void
    {
        $incoming = Message::factory()->create([
            'sender_id' => $this->other->id,
            'receiver_id' => $this->me->id,
            'is_read' => false,
        ]);
        Message::factory()->create([
            'sender_id' => $this->me->id,
            'receiver_id' => $this->other->id,
            'is_read' => false,
        ]);

        $this->getJson("/api/messages/{$this->other->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // Only what the counterpart sent me is marked read.
        $this->assertTrue((bool) $incoming->fresh()->is_read);
        $this->assertSame(1, Message::where('is_read', false)->count());
    }

    public function test_a_thread_with_an_unknown_user_returns_404(): void
    {
        $this->getJson('/api/messages/00000000-0000-0000-0000-000000000000')->assertNotFound();
    }

    public function test_conversations_group_messages_by_counterpart(): void
    {
        $third = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $this->other->id,
            'receiver_id' => $this->me->id,
            'is_read' => false,
        ]);
        Message::factory()->create([
            'sender_id' => $this->other->id,
            'receiver_id' => $this->me->id,
            'is_read' => false,
        ]);
        Message::factory()->create([
            'sender_id' => $this->me->id,
            'receiver_id' => $third->id,
            'is_read' => false,
        ]);

        $response = $this->getJson('/api/conversations')->assertOk();

        $conversations = collect($response->json())->keyBy('counterpart.id');

        $this->assertCount(2, $conversations);
        $this->assertSame(2, $conversations[$this->other->id]['unread_count']);
        $this->assertSame(0, $conversations[$third->id]['unread_count']);
    }

    public function test_the_unread_message_count_is_returned(): void
    {
        Message::factory()->count(3)->create([
            'sender_id' => $this->other->id,
            'receiver_id' => $this->me->id,
            'is_read' => false,
        ]);

        $this->getJson('/api/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('count', 3);
    }
}
