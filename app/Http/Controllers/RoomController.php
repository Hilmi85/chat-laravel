<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRoomRequest;
use App\Http\Requests\JoinRoomRequest;
use App\Models\Room;
use App\Support\ChatSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Create a new temporary room and join it as the first participant.
     */
    public function store(CreateRoomRequest $request): RedirectResponse
    {
        $name = $request->validated('name');

        $room = DB::transaction(function () use ($request, $name) {
            $room = Room::create([
                'code' => Room::generateCode(),
                'expired_at' => now()->addHours((int) config('chat.room_ttl_hours', 24)),
            ]);

            $participant = $room->participants()->create([
                'name' => $name,
                'last_seen' => now(),
            ]);

            $room->messages()->create([
                'participant_id' => null,
                'message' => "{$name} joined the room",
            ]);

            ChatSession::put($request, $room, $participant);

            return $room;
        });

        return redirect()->route('rooms.show', $room->code);
    }

    /**
     * Show a room, or the join gate when the visitor has not joined yet.
     */
    public function show(Request $request, Room $room): View
    {
        $participant = ChatSession::participant($request, $room);

        if ($participant) {
            $participant->update([
                'last_seen' => now(),
                'typing_at' => null,
            ]);
        } else {
            // Drop a stale session entry pointing to a participant that has
            // already left the room.
            ChatSession::forget($request, $room);
        }

        $messages = $room->messages()
            ->with('participant')
            ->orderBy('id')
            ->get();

        $onlineCount = $room->participants()->online()->count();

        return view('rooms.show', compact('room', 'participant', 'messages', 'onlineCount'));
    }

    /**
     * Join an existing room.
     */
    public function join(JoinRoomRequest $request, Room $room): RedirectResponse
    {
        if (ChatSession::participant($request, $room)) {
            return redirect()->route('rooms.show', $room->code);
        }

        $name = $request->validated('name');

        $participant = DB::transaction(function () use ($request, $room, $name) {
            $name = $this->uniqueParticipantName($room, $name);

            $participant = $room->participants()->create([
                'name' => $name,
                'last_seen' => now(),
            ]);

            $room->messages()->create([
                'participant_id' => null,
                'message' => "{$name} joined the room",
            ]);

            ChatSession::put($request, $room, $participant);

            return $participant;
        });

        return redirect()->route('rooms.show', $room->code);
    }

    /**
     * Leave a room and record the system message.
     */
    public function leave(Request $request, Room $room): RedirectResponse
    {
        $participant = ChatSession::participant($request, $room);

        if ($participant) {
            DB::transaction(function () use ($room, $participant) {
                $participant->update([
                    'last_seen' => null,
                    'typing_at' => null,
                ]);

                $room->messages()->create([
                    'participant_id' => null,
                    'message' => "{$participant->name} left the room",
                ]);
            });
        }

        // Always clear the session, even when the participant was already
        // marked as left (for example after a stale request).
        ChatSession::forget($request, $room);

        return redirect()->route('home')->with('status', 'You left the room.');
    }

    /**
     * Append a numeric suffix when a name is already used in the room.
     */
    private function uniqueParticipantName(Room $room, string $name): string
    {
        $candidate = $name;
        $suffix = 1;

        // Compare case-insensitively so the behaviour matches MySQL's
        // case-insensitive unique index and also works on SQLite.
        while ($room->participants()->whereRaw('LOWER(name) = ?', [Str::lower($candidate)])->exists()) {
            $suffix++;
            $suffixText = " ({$suffix})";
            $candidate = Str::limit($name, 30 - Str::length($suffixText), '').$suffixText;
        }

        return $candidate;
    }
}
