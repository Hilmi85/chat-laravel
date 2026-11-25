<?php

namespace App\Support;

use App\Models\Participant;
use App\Models\Room;
use Illuminate\Http\Request;

final class ChatSession
{
    /**
     * Get the session key for a room participant.
     */
    public static function key(Room $room): string
    {
        return "chat.participant.{$room->code}";
    }

    /**
     * Resolve the current participant for the given room.
     */
    public static function participant(Request $request, Room $room): ?Participant
    {
        $participantId = $request->session()->get(self::key($room));

        if (! $participantId) {
            return null;
        }

        return $room->participants()
            ->whereKey($participantId)
            ->whereNotNull('last_seen')
            ->first();
    }

    /**
     * Store the participant in the session.
     */
    public static function put(Request $request, Room $room, Participant $participant): void
    {
        $request->session()->put(self::key($room), $participant->id);
    }

    /**
     * Remove the participant from the session.
     */
    public static function forget(Request $request, Room $room): void
    {
        $request->session()->forget(self::key($room));
    }
}
