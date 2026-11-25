<?php

return [
    'room_ttl_hours' => (int) env('CHAT_ROOM_TTL_HOURS', 24),
    'room_code_length' => 8,

    'online_window_seconds' => (int) env('CHAT_ONLINE_WINDOW_SECONDS', 30),
    'typing_window_seconds' => (int) env('CHAT_TYPING_WINDOW_SECONDS', 3),
    'typing_debounce_ms' => (int) env('CHAT_TYPING_DEBOUNCE_MS', 300),

    'poll_interval_ms' => (int) env('CHAT_POLL_INTERVAL_MS', 2000),
    'max_name_length' => (int) env('CHAT_MAX_NAME_LENGTH', 30),
    'max_message_length' => (int) env('CHAT_MAX_MESSAGE_LENGTH', 500),
    'message_rate_limit_per_second' => (int) env('CHAT_MESSAGE_RATE_LIMIT', 1),
];
