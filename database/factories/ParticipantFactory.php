<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
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
            'name' => fake()->unique()->firstName(),
            'last_seen' => now(),
            'typing_at' => null,
        ];
    }

    /**
     * Indicate that the participant is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_seen' => null,
            'typing_at' => null,
        ]);
    }

    /**
     * Indicate that the participant is currently typing.
     */
    public function typing(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_seen' => now(),
            'typing_at' => now(),
        ]);
    }
}
