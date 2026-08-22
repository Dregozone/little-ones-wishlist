@props([
    'item',
    'state' => 'available',
    'index' => 0,
])

@php
    // Colour alone never carries the state: each variant also supplies an icon and a
    // written label, so the card is unambiguous without colour vision.
    $variants = [
        'available' => [
            'ring' => 'border-oat-300 dark:border-oat-800',
            'chip' => 'bg-oat-200 text-oat-700 dark:bg-oat-800 dark:text-oat-300',
            'icon' => 'gift',
            'label' => __('Available'),
        ],
        'taken' => [
            'ring' => 'border-dusk-300 dark:border-dusk-700',
            'chip' => 'bg-dusk-500/15 text-dusk-700 dark:text-dusk-300',
            'icon' => 'lock-closed',
            'label' => __('Someone is getting this'),
        ],
        'yours' => [
            'ring' => 'border-sage-400 ring-2 ring-sage-500/30 dark:border-sage-500',
            'chip' => 'bg-sage-500/15 text-sage-700 dark:text-sage-300',
            'icon' => 'check-circle',
            'label' => __("You're getting this"),
        ],
    ];

    $variant = $variants[$state];
@endphp

<article
    wire:key="item-{{ $item->id }}"
    style="animation-delay: {{ min($index, 12) * 35 }}ms"
    class="wl-rise wl-transition flex flex-col overflow-hidden rounded-2xl border bg-oat-50 shadow-sm shadow-oat-900/5 dark:bg-oat-900 dark:shadow-black/20 {{ $variant['ring'] }}"
>
    <div class="relative p-2 pb-0">
        <x-wishlist.remote-image
            :src="$item->image_url"
            :alt="$item->name"
            class="{{ $state === 'taken' ? 'opacity-60 saturate-50' : '' }}"
        />

        <span class="absolute bottom-2 end-4 rounded-full bg-oat-950/85 px-2.5 py-1 text-xs font-semibold tabular-nums text-oat-50 backdrop-blur-sm">
            {{ $item->formatted_price }}
        </span>
    </div>

    <div class="flex flex-1 flex-col gap-1.5 p-4">
        <p class="text-[0.68rem] font-semibold uppercase tracking-widest text-oat-600 dark:text-oat-400">
            {{ $item->shop_name }}
        </p>

        <flux:heading size="lg" class="leading-snug text-oat-900 dark:text-oat-100">
            {{ $item->name }}
        </flux:heading>

        @if (filled($item->description))
            <p class="line-clamp-3 text-sm/relaxed text-oat-700 dark:text-oat-300">
                {{ $item->description }}
            </p>
        @endif

        @if (filled($item->product_url))
            <a
                href="{{ $item->product_url }}"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-1 inline-flex min-h-11 w-fit items-center gap-1 text-sm font-medium text-sage-700 underline-offset-4 hover:underline focus-visible:underline dark:text-sage-300"
            >
                {{ __('View in shop') }}
                <flux:icon.arrow-top-right-on-square class="size-3.5" />
                <span class="sr-only">{{ __('(opens in a new tab)') }}</span>
            </a>
        @endif

        <div class="mt-auto pt-3">
            {{ $slot }}
        </div>

        <p class="flex items-center gap-1.5 pt-2 text-xs font-medium {{ $variant['chip'] }} -mx-1 rounded-lg px-2 py-1.5">
            <flux:icon :name="$variant['icon']" class="size-3.5 shrink-0" />
            {{ $variant['label'] }}
        </p>
    </div>
</article>
