@extends('layouts.app')

@section('title', 'Room '.$room->code.' — TempChat')

@section('content')
    @php
        $shareUrl = route('rooms.show', $room->code);
        $shareText = 'Join my TempChat room: '.$shareUrl;
    @endphp

    <div
        id="chat-app"
        class="flex h-dvh flex-col overflow-hidden"
        data-room-code="{{ $room->code }}"
        data-participant-id="{{ $participant?->id }}"
        data-expired-at="{{ $room->expired_at->toIso8601String() }}"
        data-server-time="{{ now()->toIso8601String() }}"
        data-poll-interval="{{ (int) config('chat.poll_interval_ms', 2000) }}"
        data-typing-debounce="{{ (int) config('chat.typing_debounce_ms', 300) }}"
        data-messages-url="{{ route('messages.index', $room->code) }}"
        data-messages-store-url="{{ route('messages.store', $room->code) }}"
        data-typing-url="{{ route('typing.store', $room->code) }}"
        data-typing-status-url="{{ route('typing.status', $room->code) }}"
        data-leave-url="{{ route('rooms.leave', $room->code) }}"
        data-home-url="{{ route('home') }}"
        data-last-message-id="{{ $messages->last()?->id ?? 0 }}"
        data-sound-key="tempchat-sound"
    >
        <header class="z-10 border-b border-secondary/20 bg-neutral/95 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-8">
                <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                    <a href="{{ route('home') }}" class="inline-flex shrink-0 items-center gap-2.5 text-primary" aria-label="TempChat home">
                        <span class="flex h-7 w-7 items-center justify-center rounded-sm border border-tertiary/40 bg-tertiary/10 font-mono text-[0.62rem] text-tertiary">T</span>
                        <span class="font-mono text-[0.62rem] font-medium uppercase tracking-[0.2em]">TempChat</span>
                    </a>
                    <span class="h-7 w-px shrink-0 bg-secondary/25"></span>
                    <div class="flex min-w-0 items-center gap-2.5">
                        <div class="min-w-0">
                            <span class="block font-mono text-[0.52rem] uppercase tracking-[0.15em] text-secondary">Room</span>
                            <span class="block truncate font-mono text-[0.68rem] tracking-[0.16em] text-primary">{{ $room->code }}</span>
                        </div>
                        <button
                            id="copy-link"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-sm border border-secondary/25 px-2.5 py-1.5 font-mono text-[0.55rem] uppercase tracking-[0.1em] text-secondary transition hover:border-secondary/50 hover:text-primary focus:outline-none focus:ring-2 focus:ring-tertiary/30"
                        >
                            <svg class="h-3 w-3" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M6.5 5.5V4.25A1.75 1.75 0 0 1 8.25 2.5h3.5A1.75 1.75 0 0 1 13.5 4.25v3.5a1.75 1.75 0 0 1-1.75 1.75H10.5M5.25 6.5h3.5A1.75 1.75 0 0 1 10.5 8.25v3.5a1.75 1.75 0 0 1-1.75 1.75h-3.5A1.75 1.75 0 0 1 3.5 11.75v-3.5A1.75 1.75 0 0 1 5.25 6.5Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
                            </svg>
                            Copy link
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 font-mono text-[0.57rem] uppercase tracking-[0.1em] text-secondary sm:gap-2.5">
                    <span class="inline-flex items-center gap-2 rounded-sm border border-outline bg-surface px-2.5 py-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-tertiary"></span>
                        <span id="online-count">{{ $onlineCount }} online</span>
                    </span>

                    <span class="inline-flex items-center gap-2 rounded-sm border border-secondary/20 px-2.5 py-2" title="Time remaining">
                        <span class="text-secondary/70">Ends in</span>
                        <span id="countdown" class="tabular-nums text-primary">24:00:00</span>
                    </span>

                    @if ($participant)
                    <button
                        id="sound-toggle"
                        type="button"
                        class="rounded-sm border border-secondary/20 px-2.5 py-2 transition hover:border-secondary/50 hover:text-primary focus:outline-none focus:ring-2 focus:ring-tertiary/30"
                        aria-pressed="true"
                    >
                        Sound on
                    </button>

                    <details class="relative">
                        <summary class="cursor-pointer list-none rounded-sm border border-secondary/20 px-2.5 py-2 transition hover:border-secondary/50 hover:text-primary focus:outline-none focus:ring-2 focus:ring-tertiary/30">Share</summary>
                        <div class="absolute right-0 z-20 mt-2 w-40 rounded-md border border-secondary/25 bg-surface p-1.5 shadow-xl">
                            <a
                                href="https://wa.me/?text={{ urlencode($shareText) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block rounded-sm px-3 py-2.5 text-[0.58rem] uppercase tracking-[0.12em] text-primary transition hover:bg-neutral"
                            >
                                WhatsApp
                            </a>
                            <a
                                href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ urlencode('Join my TempChat room') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block rounded-sm px-3 py-2.5 text-[0.58rem] uppercase tracking-[0.12em] text-primary transition hover:bg-neutral"
                            >
                                Telegram
                            </a>
                        </div>
                    </details>

                    <button
                        id="leave-room"
                        type="button"
                        class="rounded-sm border border-secondary/20 px-2.5 py-2 text-secondary transition hover:border-danger/50 hover:text-danger focus:outline-none focus:ring-2 focus:ring-tertiary/30"
                    >
                        Leave
                    </button>
                    @else
                        <span class="rounded-sm border border-outline bg-surface px-2.5 py-2 text-secondary">Invite only</span>
                    @endif
                </div>
            </div>
        </header>

        <main class="mx-auto flex min-h-0 w-full max-w-5xl flex-1 flex-col overflow-hidden bg-chat">
            <section id="messages" class="chat-scroll min-h-0 flex-1 overflow-y-auto px-4 pb-5 pt-6 sm:px-8 sm:pb-8 sm:pt-8" aria-live="polite">
                <div id="empty-state" class="flex h-full flex-col items-center justify-center gap-3 py-16 text-center {{ $messages->isEmpty() ? '' : 'hidden' }}">
                    <div class="mb-2 flex h-12 w-12 items-center justify-center rounded-md border border-secondary/25 bg-surface font-mono text-sm text-secondary">
                        <span>01</span>
                    </div>
                    @if ($participant)
                        <p class="font-mono text-[0.65rem] uppercase tracking-[0.18em] text-primary">The room is yours</p>
                        <p class="max-w-xs text-sm leading-6 text-secondary">
                            Send the first message or share this room link to bring someone in.
                        </p>
                    @else
                        <p class="font-mono text-[0.65rem] uppercase tracking-[0.18em] text-primary">Room preview</p>
                        <p class="max-w-xs text-sm leading-6 text-secondary">
                            Choose a name below to join the conversation.
                        </p>
                    @endif
                </div>

                <div id="message-list" class="mx-auto max-w-3xl space-y-5">
                    @foreach ($messages as $message)
                        @include('rooms.partials.message', ['message' => $message, 'participant' => $participant])
                    @endforeach
                </div>
            </section>

            @if ($participant)
            <div id="typing-indicator" class="hidden px-4 pb-2 sm:px-8">
                <div class="mx-auto flex max-w-3xl items-center gap-2 font-mono text-[0.6rem] uppercase tracking-[0.12em] text-secondary">
                    <span id="typing-text"></span>
                    <span class="flex gap-1" aria-hidden="true">
                        <span class="typing-dot h-1 w-1 rounded-full bg-tertiary"></span>
                        <span class="typing-dot h-1 w-1 rounded-full bg-tertiary"></span>
                        <span class="typing-dot h-1 w-1 rounded-full bg-tertiary"></span>
                    </span>
                </div>
            </div>

            <div class="border-t border-secondary/20 bg-surface/35 px-4 py-4 sm:px-8 sm:py-5">
                <form id="message-form" class="mx-auto flex max-w-3xl items-end gap-2.5">
                    @csrf

                    <div class="relative flex-1">
                        <input
                            id="message-input"
                            name="message"
                            type="text"
                            maxlength="{{ (int) config('chat.max_message_length', 500) }}"
                            autocomplete="off"
                            placeholder="{{ $participant ? 'Write a message…' : 'Join the room to chat' }}"
                            class="w-full rounded-md border border-secondary/25 bg-neutral px-4 py-3.5 pr-16 text-sm text-primary placeholder:text-secondary/60 transition focus:border-tertiary focus:outline-none focus:ring-2 focus:ring-tertiary/15 disabled:cursor-not-allowed disabled:opacity-50"
                            @disabled(!$participant)
                        >
                        <span id="char-count" class="pointer-events-none absolute bottom-3 right-3 font-mono text-[0.6rem] text-secondary">
                            0/{{ (int) config('chat.max_message_length', 500) }}
                        </span>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-md bg-tertiary px-5 py-3.5 font-mono text-[0.62rem] font-medium uppercase tracking-[0.14em] text-on-primary transition hover:bg-tertiary/90 focus:outline-none focus:ring-2 focus:ring-tertiary/40 disabled:cursor-not-allowed disabled:opacity-50"
                        @disabled(!$participant)
                    >
                        Send
                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M2 8h11m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </form>

                <p class="mx-auto mt-2.5 max-w-3xl font-mono text-[0.56rem] uppercase tracking-[0.12em] text-secondary">
                    Messages disappear when the room expires
                </p>
            </div>
            @else
                <div class="border-t border-outline bg-surface px-4 py-4 sm:px-8 sm:py-5">
                    <div class="mx-auto max-w-3xl">
                        <div class="mb-3 flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-sm border border-tertiary/35 bg-tertiary/10 font-mono text-xs text-tertiary">+</span>
                            <div>
                                <p class="font-mono text-[0.62rem] uppercase tracking-[0.14em] text-primary">You are invited</p>
                                <p class="mt-0.5 text-xs text-secondary">Choose a name to join this conversation.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('rooms.join', $room->code) }}" class="flex items-end gap-2.5">
                            @csrf
                            <div class="min-w-0 flex-1">
                                <label for="join-name" class="sr-only">Your display name</label>
                                <input
                                    id="join-name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name') }}"
                                    maxlength="{{ (int) config('chat.max_name_length', 30) }}"
                                    required
                                    autocomplete="nickname"
                                    placeholder="Your display name"
                                    class="w-full rounded-md border border-outline bg-neutral px-4 py-3.5 text-sm text-primary placeholder:text-secondary/60 transition focus:border-tertiary focus:outline-none focus:ring-2 focus:ring-tertiary/20"
                                >
                            </div>
                            <button
                                type="submit"
                                class="inline-flex shrink-0 items-center gap-2 rounded-md bg-tertiary px-5 py-3.5 font-mono text-[0.62rem] font-medium uppercase tracking-[0.14em] text-on-primary transition hover:bg-tertiary/90 focus:outline-none focus:ring-2 focus:ring-tertiary/40"
                            >
                                Join room
                                <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <path d="M2 8h11m-4-4 4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                        @error('name')
                            <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 font-mono text-[0.55rem] uppercase tracking-[0.1em] text-secondary">No account needed · Your name is only used in this room</p>
                    </div>
                </div>
            @endif
        </main>

        <div id="expired-overlay" class="fixed inset-0 z-[70] hidden items-center justify-center bg-neutral/90 p-4 backdrop-blur">
            <div class="w-full max-w-sm rounded-lg border border-secondary/20 bg-surface p-6 text-center">
                <p class="font-mono text-[0.65rem] uppercase tracking-[0.2em] text-warning">Room expired</p>
                <h2 class="mt-3 font-mono text-xl font-medium text-primary">This conversation is gone.</h2>
                <p class="mt-2 text-sm leading-6 text-secondary">
                    Temporary rooms are deleted after 24 hours.
                </p>
                <a
                    href="{{ route('home') }}"
                    class="mt-6 inline-flex w-full items-center justify-center rounded-md bg-tertiary px-5 py-3 font-mono text-xs font-medium uppercase tracking-[0.18em] text-on-primary transition hover:bg-tertiary/90"
                >
                    Create a new room
                </a>
            </div>
        </div>
    </div>
@endsection
