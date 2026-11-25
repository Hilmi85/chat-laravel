<?php

namespace App\Support;

use App\Models\Message;

final class MessagePayload
{
    /**
     * Format a message for the polling and send-message JSON responses.
     *
     * @return array<string, mixed>
     */
    public static function make(Message $message, ?int $currentParticipantId): array
    {
        $participant = $message->participant;

        return [
            'id' => $message->id,
            'name' => $participant?->name,
            'message' => $message->message,
            'is_system' => $message->isSystem(),
            'is_own' => $participant !== null && $participant->id === $currentParticipantId,
            'time' => $message->created_at->format('H:i'),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
