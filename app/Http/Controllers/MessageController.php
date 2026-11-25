<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\Room;
use App\Support\ChatSession;
use App\Support\MessagePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class MessageController extends Controller
{
    /**
     * Poll for messages that arrived after the last known message ID.
     */
    public function index(Request $request, Room $room): JsonResponse
    {
        $participant = ChatSession::participant($request, $room);

        if (! $participant) {
            return response()->json(['message' => 'Join the room first.'], 403);
        }

        $participant->update(['last_seen' => now()]);

        $afterId = max(0, (int) $request->query('after_id', 0));

        $messages = $room->messages()
            ->with('participant')
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(200)
            ->get();

        return response()->json([
            'messages' => $messages
                ->map(fn (Message $message) => MessagePayload::make($message, $participant->id))
                ->values(),
            'online_count' => $room->participants()->online()->count(),
            'expired_at' => $room->expired_at->toIso8601String(),
            'server_time' => now()->toIso8601String(),
            'current_participant_id' => $participant->id,
            'last_message_id' => $messages->last()?->id,
        ]);
    }

    /**
     * Store a new chat message.
     */
    public function store(StoreMessageRequest $request, Room $room): JsonResponse
    {
        $participant = ChatSession::participant($request, $room);

        if (! $participant) {
            return response()->json(['message' => 'Join the room first.'], 403);
        }

        $rateLimitKey = "chat-message:{$participant->id}";
        $limit = max(1, (int) config('chat.message_rate_limit_per_second', 1));

        if (RateLimiter::tooManyAttempts($rateLimitKey, $limit)) {
            return response()->json([
                'message' => 'Slow down a little.',
                'retry_after' => RateLimiter::availableIn($rateLimitKey),
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 1);

        $message = $room->messages()->create([
            'participant_id' => $participant->id,
            'message' => $request->validated('message'),
        ]);

        $message->load('participant');

        $participant->update([
            'last_seen' => now(),
            'typing_at' => null,
        ]);

        return response()->json([
            'message' => MessagePayload::make($message, $participant->id),
            'online_count' => $room->participants()->online()->count(),
        ], 201);
    }
}
