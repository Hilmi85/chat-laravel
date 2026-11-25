<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Support\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TypingController extends Controller
{
    /**
     * Mark the current participant as typing.
     */
    public function store(Request $request, Room $room): Response|JsonResponse
    {
        $participant = ChatSession::participant($request, $room);

        if (! $participant) {
            return response()->json(['message' => 'Join the room first.'], 403);
        }

        $participant->update([
            'typing_at' => now(),
            'last_seen' => now(),
        ]);

        return response()->noContent();
    }

    /**
     * Read who is currently typing. This endpoint must never write timestamps.
     */
    public function status(Request $request, Room $room): JsonResponse
    {
        $participant = ChatSession::participant($request, $room);

        if (! $participant) {
            return response()->json(['message' => 'Join the room first.'], 403);
        }

        $names = $room->participants()
            ->typing()
            ->where('id', '!=', $participant->id)
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return response()->json(['typing' => $names]);
    }
}
