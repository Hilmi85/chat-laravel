<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Support\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Start a conversation');
    }

    public function test_user_can_create_a_room(): void
    {
        $this->travelTo(now());

        $response = $this->post(route('rooms.store'), ['name' => 'Rafif']);

        $room = Room::query()->firstOrFail();
        $participant = $room->participants()->firstOrFail();

        $response->assertRedirect(route('rooms.show', $room->code));

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $room->code);
        $this->assertSame(now()->addHours(24)->timestamp, $room->expired_at->timestamp);

        $this->assertDatabaseHas('participants', [
            'room_id' => $room->id,
            'name' => 'Rafif',
        ]);

        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'participant_id' => null,
            'message' => 'Rafif joined the room',
        ]);

        $this->assertSame($participant->id, session(ChatSession::key($room)));
    }

    public function test_user_can_join_an_existing_room(): void
    {
        $room = Room::factory()->create();

        $response = $this->post(route('rooms.join', $room->code), ['name' => 'Budi']);

        $response->assertRedirect(route('rooms.show', $room->code));

        $this->assertDatabaseHas('participants', [
            'room_id' => $room->id,
            'name' => 'Budi',
        ]);

        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'message' => 'Budi joined the room',
        ]);
    }

    public function test_duplicate_names_receive_a_suffix(): void
    {
        $room = Room::factory()->create();
        $room->participants()->create([
            'name' => 'Rafif',
            'last_seen' => now(),
        ]);

        $this->post(route('rooms.join', $room->code), ['name' => 'Rafif'])
            ->assertRedirect(route('rooms.show', $room->code));

        $this->assertDatabaseHas('participants', [
            'room_id' => $room->id,
            'name' => 'Rafif (2)',
        ]);
    }

    public function test_user_can_leave_a_room(): void
    {
        $room = Room::factory()->create();
        $participant = $room->participants()->create([
            'name' => 'Rafif',
            'last_seen' => now(),
            'typing_at' => now(),
        ]);

        $response = $this
            ->withSession([ChatSession::key($room) => $participant->id])
            ->post(route('rooms.leave', $room->code));

        $response->assertRedirect(route('home'));

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'last_seen' => null,
            'typing_at' => null,
        ]);

        $this->assertDatabaseHas('messages', [
            'room_id' => $room->id,
            'message' => 'Rafif left the room',
        ]);

        $this->assertNull(session(ChatSession::key($room)));
    }

    public function test_room_page_renders_for_a_joined_participant(): void
    {
        $room = Room::factory()->create();
        $participant = $room->participants()->create([
            'name' => 'Rafif',
            'last_seen' => now(),
        ]);

        $room->messages()->create([
            'participant_id' => $participant->id,
            'message' => 'Hello from the room',
        ]);

        $this->withSession([ChatSession::key($room) => $participant->id])
            ->get(route('rooms.show', $room->code))
            ->assertOk()
            ->assertSee($room->code)
            ->assertSee('Hello from the room')
            ->assertSee('Write a message');
    }

    public function test_room_page_shows_the_join_gate_for_guests(): void
    {
        $room = Room::factory()->create();

        $this->get(route('rooms.show', $room->code))
            ->assertOk()
            ->assertSee('Join the conversation')
            ->assertSee('Join room');
    }

    public function test_expired_room_is_deleted_and_redirects_web_visitors(): void
    {
        $room = Room::factory()->expired()->create();

        $this->get(route('rooms.show', $room->code))
            ->assertRedirect(route('home'));

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_expired_room_returns_gone_for_json_requests(): void
    {
        $room = Room::factory()->expired()->create();

        $this->getJson(route('rooms.show', $room->code))
            ->assertStatus(410)
            ->assertJson(['message' => 'This room has expired.']);

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_name_is_required_and_limited(): void
    {
        $this->post(route('rooms.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->post(route('rooms.store'), ['name' => str_repeat('a', 31)])
            ->assertSessionHasErrors('name');
    }

    public function test_name_is_sanitized(): void
    {
        $this->post(route('rooms.store'), [
            'name' => '<script>alert(1)</script>Rafif',
        ]);

        $this->assertDatabaseHas('participants', ['name' => 'Rafif']);
    }

    public function test_name_special_characters_are_rendered_once(): void
    {
        $this->post(route('rooms.store'), ['name' => 'Rafif & Co <3']);

        $room = Room::query()->firstOrFail();
        $participant = $room->participants()->firstOrFail();

        $this->assertSame('Rafif & Co <3', $participant->name);

        $this->withSession([ChatSession::key($room) => $participant->id])
            ->get(route('rooms.show', $room->code))
            ->assertOk()
            ->assertSee('Rafif &amp; Co &lt;3 joined the room', false)
            ->assertDontSee('&amp;amp;', false);
    }

    public function test_unknown_room_shows_a_friendly_404_page(): void
    {
        $this->get('/room/ZZZZZZZZ')
            ->assertNotFound()
            ->assertSee('Room not found');
    }
}
