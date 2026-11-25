<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['code', 'expired_at'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
        ];
    }

    /**
     * Get the room's participants.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Get the room's messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Scope a query to only include active rooms.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expired_at', '>', now());
    }

    /**
     * Scope a query to only include expired rooms.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expired_at', '<=', now());
    }

    /**
     * Determine whether the room has expired.
     */
    public function isExpired(): bool
    {
        return $this->expired_at->isPast();
    }

    /**
     * Generate a unique, collision-resistant room code.
     */
    public static function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
