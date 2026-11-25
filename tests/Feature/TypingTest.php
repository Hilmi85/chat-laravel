<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Room;
use App\Support\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypingTest extends TestCase
{
    use RefreshDatabase;

    public function test_typing_updates_typing_and_last_seen_timestamps(): void
    {
        [$room, $participant] = $this->joinedRoom();

        $this->travelTo(now());

        $this
            ->asParticipant($room, $participant)
            ->postJson(route('typing.store', $room->code))
            ->assertNoContent();

        $participant->refresh();

        $this->assertNotNull($participant->typing_at);
        $this->assertNotNull($participant->last_seen);
        $this->assertSame(now()->timestamp, $participant->typing_at->timestamp);
    }

    public function test_typing_status_is_read_only_and_excludes_self(): void
    {
        $room = Room::factory()->create();

        $self = $room->participants()->create([
            'name' => 'Self',
            'last_seen' => now(),
            'typing_at' => now(),
        ]);

        $other = $room->participants()->create([
            'name' => 'Other',
            'last_seen' => now(),
            'typing_at' => now(),
        ]);

        $otherTypingAt = $other->typing_at;

        $response = $this
            ->withSession([ChatSession::key($room) => $self->id])
            ->getJson(route('typing.status', $room->code));

        $response
            ->assertOk()
            ->assertJson(['typing' => ['Other']]);

        $other->refresh();
        $self->refresh();

        $this->assertSame($otherTypingAt->timestamp, $other->typing_at->timestamp);
        $this->assertNotNull($self->typing_at);
    }

    public function test_stale_typing_is_excluded(): void
    {
        $room = Room::factory()->create();

        $self = $room->participants()->create([
            'name' => 'Self',
            'last_seen' => now(),
        ]);

        $room->participants()->create([
            'name' => 'Stale',
            'last_seen' => now(),
            'typing_at' => now()->subSeconds(4),
        ]);

        $this
            ->withSession([ChatSession::key($room) => $self->id])
            ->getJson(route('typing.status', $room->code))
            ->assertOk()
            ->assertJson(['typing' => []]);
    }

    public function test_guest_cannot_update_typing_status(): void
    {
        $room = Room::factory()->create();

        $this->postJson(route('typing.store', $room->code))
            ->assertStatus(403);
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
