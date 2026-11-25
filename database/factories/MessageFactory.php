<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Participant;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'participant_id' => null,
            'message' => fake()->sentence(),
        ];
    }

    /**
     * Attach the message to a participant and its room.
     */
    public function fromParticipant(Participant $participant): static
    {
        return $this->state(fn (array $attributes) => [
            'room_id' => $participant->room_id,
            'participant_id' => $participant->id,
        ]);
    }
}
