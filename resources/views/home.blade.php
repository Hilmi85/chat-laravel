@extends('layouts.app')

@section('title', 'TempChat — Temporary chat rooms')

@section('content')
    <main class="flex min-h-dvh flex-col">
        <header class="mx-auto flex w-full max-w-6xl items-center justify-between border-b border-secondary/15 px-5 py-5 sm:px-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-primary" aria-label="TempChat home">
                <span class="flex h-8 w-8 items-center justify-center rounded-md border border-tertiary/40 bg-tertiary/10 font-mono text-xs text-tertiary">T</span>
                <span class="font-mono text-xs font-medium uppercase tracking-[0.24em]">TempChat</span>
            </a>
            <span class="inline-flex items-center gap-2 rounded-full border border-outline bg-surface px-3 py-1.5 font-mono text-[0.62rem] uppercase tracking-[0.12em] text-secondary">
                <span class="h-1.5 w-1.5 rounded-full bg-tertiary"></span>
                Private by default
            </span>
        </header>

        <section class="mx-auto flex w-full max-w-6xl flex-1 items-center px-5 py-14 sm:px-8 sm:py-20">
            <div class="grid w-full gap-12 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-center lg:gap-20">
                <div class="max-w-2xl">
                    <p class="mb-6 inline-flex items-center gap-3 font-mono text-[0.65rem] uppercase tracking-[0.22em] text-secondary">
                        <span class="h-px w-8 bg-secondary/50"></span>
                        Temporary chat, made simple
                    </p>

                    <h1 class="font-mono text-4xl font-medium leading-[1.12] tracking-[-0.045em] text-primary sm:text-5xl lg:text-[3.45rem]">
                        A private room.<br>
                        <span class="text-secondary">A conversation with an end.</span>
                    </h1>

                    <div class="mt-7 max-w-lg border-l-2 border-tertiary/60 pl-4 sm:pl-5">
                        <p class="text-base font-medium leading-7 text-primary sm:text-lg">
                            Start a room, send the link, and talk freely.
                        </p>
                        <p class="mt-1.5 text-sm leading-6 text-secondary">
                            No account to create. No chat history to manage.
                        </p>
                    </div>

                    <div class="mt-8 grid max-w-xl grid-cols-1 border-y border-secondary/20 sm:grid-cols-3">
                        <div class="py-4 sm:pr-4">
                            <p class="font-mono text-[0.6rem] uppercase tracking-[0.14em] text-secondary">01 / Access</p>
                            <p class="mt-2 text-sm text-primary">No account needed</p>
                        </div>
                        <div class="border-t border-secondary/15 py-4 sm:border-l sm:border-t-0 sm:px-4">
                            <p class="font-mono text-[0.6rem] uppercase tracking-[0.14em] text-secondary">02 / Invite</p>
                            <p class="mt-2 text-sm text-primary">Share one simple link</p>
                        </div>
                        <div class="border-t border-secondary/15 py-4 sm:border-l sm:border-t-0 sm:pl-4">
                            <p class="font-mono text-[0.6rem] uppercase tracking-[0.14em] text-secondary">03 / Expiry</p>
                            <p class="mt-2 text-sm text-primary">Deleted after 24 hours</p>
                        </div>
                    </div>
                </div>

                <section class="relative overflow-hidden rounded-lg border border-outline border-t-2 border-t-tertiary bg-panel p-6 shadow-[0_24px_80px_rgba(0,0,0,0.32)] sm:p-8" aria-labelledby="create-room-title">
                    <div class="mb-7 flex items-start justify-between gap-4">
                        <div>
                            <p class="font-mono text-[0.62rem] uppercase tracking-[0.2em] text-secondary">Start a conversation</p>
                            <h2 id="create-room-title" class="mt-2 font-mono text-xl font-medium tracking-tight text-primary">Create a room</h2>
                        </div>
                        <span class="rounded-sm border border-secondary/25 px-2 py-1 font-mono text-[0.6rem] uppercase tracking-[0.12em] text-secondary">24h</span>
                    </div>

                    @if (session('error'))
                        <div class="mb-5 rounded-md border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="mb-5 rounded-md border border-tertiary/30 bg-tertiary/10 px-4 py-3 text-sm text-tertiary">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('rooms.store') }}" class="space-y-5">
                        @csrf

                        <div class="space-y-2">
                            <label for="name" class="block font-mono text-[0.62rem] uppercase tracking-[0.16em] text-secondary">
                                What should we call you?
                            </label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                maxlength="{{ (int) config('chat.max_name_length', 30) }}"
                                required
                                autofocus
                                autocomplete="nickname"
                                placeholder="Your display name"
                                class="w-full rounded-md border border-secondary/25 bg-neutral px-4 py-3.5 text-sm text-primary placeholder:text-secondary/55 transition focus:border-tertiary focus:outline-none focus:ring-2 focus:ring-tertiary/15"
                            >
                            @error('name')
                                <p class="text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="group inline-flex w-full items-center justify-between rounded-md bg-tertiary px-5 py-3.5 font-mono text-xs font-medium uppercase tracking-[0.14em] text-on-primary transition hover:bg-tertiary/90 focus:outline-none focus:ring-2 focus:ring-tertiary/40 focus:ring-offset-2 focus:ring-offset-surface"
                        >
                            <span>Create private room</span>
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <p class="flex items-center justify-center gap-2 pt-1 text-center font-mono text-[0.6rem] uppercase tracking-[0.1em] text-secondary">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M4.5 7V5a3.5 3.5 0 0 1 7 0v2m-8 0h9v6h-9V7Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Your room is private to anyone with its link
                        </p>
                    </form>
                </section>
            </div>
        </section>

        <footer class="mx-auto w-full max-w-6xl px-5 pb-6 sm:px-8 sm:pb-8">
            <div class="flex flex-col gap-2 border-t border-secondary/15 pt-4 font-mono text-[0.58rem] uppercase tracking-[0.14em] text-secondary sm:flex-row sm:items-center sm:justify-between">
                <span>TempChat <span class="px-1 text-secondary/50">/</span> Made for conversations that can disappear</span>
                <span>Anonymous by design · 24 hour rooms</span>
            </div>
        </footer>
    </main>
@endsection
