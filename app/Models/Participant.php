<?php

namespace App\Models;

use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['room_id', 'name', 'last_seen', 'typing_at'])]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_seen' => 'datetime',
            'typing_at' => 'datetime',
        ];
    }

    /**
     * Get the room the participant belongs to.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the participant's messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Scope a query to only include online participants.
     */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('last_seen', '>=', now()->subSeconds((int) config('chat.online_window_seconds', 30)));
    }

    /**
     * Scope a query to only include participants who are typing.
     */
    public function scopeTyping(Builder $query): Builder
    {
        return $query
            ->whereNotNull('typing_at')
            ->where('typing_at', '>=', now()->subSeconds((int) config('chat.typing_window_seconds', 3)));
    }

    /**
     * Determine whether the participant is online.
     */
    public function isOnline(): bool
    {
        return $this->last_seen !== null
            && $this->last_seen->greaterThanOrEqualTo(now()->subSeconds((int) config('chat.online_window_seconds', 30)));
    }

    /**
     * Determine whether the participant is currently typing.
     */
    public function isTyping(): bool
    {
        return $this->typing_at !== null
            && $this->typing_at->greaterThanOrEqualTo(now()->subSeconds((int) config('chat.typing_window_seconds', 3)));
    }
}
