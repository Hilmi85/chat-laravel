<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupExpiredRoomsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_deletes_expired_rooms_and_cascades_related_data(): void
    {
        $expired = Room::factory()->expired()->create();
        $expiredParticipant = $expired->participants()->create([
            'name' => 'Gone',
            'last_seen' => now(),
        ]);
        $expired->messages()->create([
            'participant_id' => $expiredParticipant->id,
            'message' => 'Old message',
        ]);

        $active = Room::factory()->create();
        $activeParticipant = $active->participants()->create([
            'name' => 'Stays',
            'last_seen' => now(),
        ]);
        $active->messages()->create([
            'participant_id' => $activeParticipant->id,
            'message' => 'Still here',
        ]);

        $this->artisan('rooms:cleanup')
            ->expectsOutput('Deleted 1 expired room(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('rooms', ['id' => $expired->id]);
        $this->assertDatabaseMissing('participants', ['id' => $expiredParticipant->id]);
        $this->assertDatabaseMissing('messages', ['room_id' => $expired->id]);

        $this->assertDatabaseHas('rooms', ['id' => $active->id]);
        $this->assertDatabaseHas('participants', ['id' => $activeParticipant->id]);
        $this->assertDatabaseHas('messages', ['room_id' => $active->id]);
    }
}
