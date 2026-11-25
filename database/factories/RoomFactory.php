<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'expired_at' => now()->addHours((int) config('chat.room_ttl_hours', 24)),
        ];
    }

    /**
     * Indicate that the room has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expired_at' => now()->subMinute(),
        ]);
    }
}
