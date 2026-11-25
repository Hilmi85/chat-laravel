@extends('layouts.app')

@section('title', 'Room not found — TempChat')

@section('content')
    <main class="flex min-h-dvh items-center justify-center px-6">
        <div class="w-full max-w-md rounded-lg border border-outline border-t-2 border-t-tertiary bg-panel p-7 shadow-[0_24px_80px_rgba(0,0,0,0.32)] sm:p-9">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 font-mono text-[0.62rem] uppercase tracking-[0.18em] text-primary" aria-label="TempChat home">
                <span class="flex h-7 w-7 items-center justify-center rounded-sm border border-tertiary/40 bg-tertiary/10 text-[0.6rem] text-tertiary">T</span>
                TempChat
            </a>
            <div class="mt-10 border-t border-secondary/20 pt-7">
                <p class="font-mono text-[0.62rem] uppercase tracking-[0.2em] text-secondary">Room unavailable <span class="px-1 text-secondary/50">/</span> 404</p>
                <h1 class="mt-3 font-mono text-2xl font-medium tracking-tight text-primary">This room is gone.</h1>
                <p class="mt-3 text-sm leading-6 text-secondary">
                    The link may be incorrect, or the room has expired. Temporary rooms are deleted after 24 hours.
                </p>
            </div>
            <a
                href="{{ route('home') }}"
                class="mt-7 inline-flex w-full items-center justify-between rounded-md bg-tertiary px-5 py-3.5 font-mono text-xs font-medium uppercase tracking-[0.14em] text-on-primary transition hover:bg-tertiary/90 focus:outline-none focus:ring-2 focus:ring-tertiary/40"
            >
                <span>Create a new room</span>
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </a>
        </div>
    </main>
@endsection
