@props([
    'title',
    'count' => 0,
    'tone' => 'available',
    'open' => true,
    'description' => null,
])

@php
    $tones = [
        'available' => 'bg-status-available',
        'taken' => 'bg-status-taken',
        'yours' => 'bg-status-yours',
    ];

    $panelId = 'section-'.Str::slug($tone);
@endphp

@if ($count > 0)
    {{--
        A native <details> would be simpler, but the disclosure needs to animate and
        Livewire needs to be able to re-render the contents without slamming it shut, so
        this is a button plus an Alpine-controlled region with the matching ARIA wiring.
    --}}
    <section x-data="{ open: @js($open) }">
        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open ? 'true' : 'false'"
            aria-controls="{{ $panelId }}"
            class="group flex min-h-11 w-full items-center gap-3 rounded-lg text-start focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sage-600"
        >
            <span class="size-2.5 shrink-0 rounded-full {{ $tones[$tone] }}" aria-hidden="true"></span>

            <flux:heading size="lg" class="text-oat-900 dark:text-oat-100">
                {{ $title }}
            </flux:heading>

            <flux:badge size="sm" variant="pill">{{ $count }}</flux:badge>

            <flux:icon.chevron-down
                class="wl-transition ms-auto size-5 text-oat-600 dark:text-oat-400"
                ::class="open ? 'rotate-180' : ''"
            />
        </button>

        @if (filled($description))
            <p class="mt-1 ps-5.5 text-sm text-oat-600 dark:text-oat-400">{{ $description }}</p>
        @endif

        <div
            id="{{ $panelId }}"
            x-show="open"
            x-collapse
            class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            {{ $slot }}
        </div>
    </section>
@endif
