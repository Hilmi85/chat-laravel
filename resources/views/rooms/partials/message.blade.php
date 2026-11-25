@php
    $isOwn = $participant !== null && $message->participant_id === $participant->id;
    $isSystem = $message->isSystem();
@endphp

@if ($isSystem)
    <div class="flex justify-center" data-message-id="{{ $message->id }}">
        <span class="rounded-full border border-secondary/20 bg-surface/60 px-3 py-1 font-mono text-[0.65rem] uppercase tracking-[0.12em] text-secondary">
            {{ $message->message }}
        </span>
    </div>
@else
    <div class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
        <div class="max-w-[85%] sm:max-w-[70%]">
            @unless ($isOwn)
                <p class="mb-1 px-1 font-mono text-[0.65rem] uppercase tracking-[0.12em] text-secondary">
                    {{ $message->participant?->name ?? 'Anonymous' }}
                </p>
            @endunless

            <div class="rounded-lg px-4 py-3 text-sm leading-6 {{ $isOwn ? 'bg-tertiary text-on-primary' : 'border border-secondary/20 bg-surface text-primary' }}">
                <p class="whitespace-pre-wrap break-words">{{ $message->message }}</p>
            </div>

            <time
                datetime="{{ $message->created_at->toIso8601String() }}"
                data-message-time
                class="mt-1 block px-1 font-mono text-[0.6rem] text-secondary {{ $isOwn ? 'text-right' : '' }}"
            >
                {{ $message->created_at->format('H:i') }}
            </time>
        </div>
    </div>
@endif
