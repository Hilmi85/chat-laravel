<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Room;
use App\Support\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_send_a_message(): void
    {
        [$room, $participant] = $this->joinedRoom();

        $response = $this
            ->asParticipant($room, $participant)
            ->postJson(route('messages.store', $room->code), [
                'message' => 'Hello there',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message.message', 'Hello there')
            ->assertJsonPath('message.name', 'Rafif')
            ->assertJsonPath('message.is_own', true)
            ->assertJsonPath('message.is_system', false);

        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'participant_id' => $participant->id,
            'message' => 'Hello there',
        ]);
    }

    public function test_message_is_sanitized_before_storage(): void
    {
        [$room, $participant] = $this->joinedRoom();

        $response = $this
            ->asParticipant($room, $participant)
            ->postJson(route('messages.store', $room->code), [
                'message' => '<script>alert(1)</script>Hello <b>world</b>',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message.message', 'Hello world');

        $this->assertDatabaseHas('messages', ['message' => 'Hello world']);
        $this->assertDatabaseMissing('messages', ['message' => '<script>alert(1)</script>Hello <b>world</b>']);
    }

    public function test_message_special_characters_are_not_double_escaped(): void
    {
        [$room, $participant] = $this->joinedRoom();

        $response = $this
            ->asParticipant($room, $participant)
            ->postJson(route('messages.store', $room->code), [
                'message' => 'Tom & Jerry <3',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message.message', 'Tom & Jerry <3');

        $this->assertDatabaseHas('messages', ['message' => 'Tom & Jerry <3']);
        $this->assertDatabaseMissing('messages', ['message' => 'Tom &amp; Jerry &lt;3']);
    }

    public function test_a_participant_who_left_cannot_send_messages_from_a_stale_session(): void
    {
        $room = Room::factory()->create();
        $participant = $room->participants()->create([
            'name' => 'Gone',
            'last_seen' => null,
        ]);

        $this
            ->asParticipant($room, $participant)
            ->postJson(route('messages.store', $room->code), [
                'message' => 'Hello',
            ])
            ->assertStatus(403);
    }

    public function test_message_is_required_and_limited(): void
    {
        [$room, $participant] = $this->joinedRoom();

        $this->asParticipant($room, $participant)
            ->postJson(route('messages.store', $room->code), ['message' => ''])
            ->assertStatus(422);

        $this->asParticipant($room, $participant)
            ->postJson(route('messages.store', $room->code), ['message' => str_repeat('a', 501)])
            ->assertStatus(422);
    }

    public function test_messages_are_rate_limited(): void
    {
        [$room, $participant] = $this->joinedRoom();

        $this->asParticipant($room, $participant)
            ->postJson(route('messages.store', $room->code), ['message' => 'First'])
            ->assertCreated();

        $this->asParticipant($room, $participant)
            ->postJson(route('messages.store', $room->code), ['message' => 'Second'])
            ->assertStatus(429);
    }

    public function test_polling_returns_only_messages_after_the_cursor(): void
    {
        [$room, $participant] = $this->joinedRoom();

        $first = $room->messages()->create([
            'participant_id' => $participant->id,
            'message' => 'First',
        ]);

        $room->messages()->create([
            'participant_id' => $participant->id,
            'message' => 'Second',
        ]);

        $response = $this
            ->asParticipant($room, $participant)
            ->getJson(route('messages.index', $room->code).'?after_id='.$first->id);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.message', 'Second')
            ->assertJsonPath('messages.0.is_own', true);
    }

    public function test_polling_includes_system_messages_and_online_count(): void
    {
        [$room, $participant] = $this->joinedRoom();

        $room->participants()->create([
            'name' => 'Fresh',
            'last_seen' => now(),
        ]);

        $room->participants()->create([
            'name' => 'Stale',
            'last_seen' => now()->subMinutes(5),
        ]);

        $room->messages()->create([
            'participant_id' => null,
            'message' => 'Fresh joined the room',
        ]);

        $response = $this
            ->asParticipant($room, $participant)
            ->getJson(route('messages.index', $room->code));

        $response
            ->assertOk()
            ->assertJsonPath('messages.0.is_system', true)
            ->assertJsonPath('online_count', 2);
    }

    public function test_guest_cannot_send_a_message(): void
    {
        $room = Room::factory()->create();

        $this->postJson(route('messages.store', $room->code), [
            'message' => 'Hello',
        ])->assertStatus(403);
    }

    /**
     * @return array{0: Room, 1: Participant}
     */
    private function joinedRoom(): array
    {
        $room = Room::factory()->create();
        $participant = $room->participants()->create([
            'name' => 'Rafif',
            'last_seen' => now(),
        ]);

        return [$room, $participant];
    }

    private function asParticipant(Room $room, Participant $participant): static
    {
        return $this->withSession([
            ChatSession::key($room) => $participant->id,
        ]);
    }
}
