@props([
    'min' => 0,
    'max' => 100,
    'low' => null,
    'high' => null,
    'lowModel' => 'minPrice',
    'highModel' => 'maxPrice',
])

@php
    $low ??= $min;
    $high ??= $max;
    $id = 'price-range-'.Str::random(6);
@endphp

<div
    x-data="priceRange({
        min: {{ $min }},
        max: {{ $max }},
        low: {{ $low }},
        high: {{ $high }},
        onChange: (low, high) => $wire.setPriceRange(low, high),
    })"
>
    <div class="flex items-baseline justify-between gap-2">
        <span id="{{ $id }}-label" class="text-xs font-medium text-oat-700 dark:text-oat-300">
            {{ __('Price') }}
        </span>

        <span class="text-xs tabular-nums text-oat-600 dark:text-oat-400" aria-hidden="true">
            <span x-text="money(low)"></span> &ndash; <span x-text="money(high)"></span>
        </span>
    </div>

    <div class="wl-range mt-1">
        {{-- Painted track. The inputs contribute only their thumbs. --}}
        <div class="pointer-events-none absolute inset-x-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-oat-300 dark:bg-oat-800" aria-hidden="true">
            <div class="absolute h-full rounded-full bg-sage-500 dark:bg-sage-400" :style="fillStyle"></div>
        </div>

        <input
            type="range"
            :min="min"
            :max="max"
            :step="step"
            x-model.number="low"
            @input="clampLow()"
            :aria-valuetext="money(low)"
            aria-label="{{ __('Minimum price') }}"
            data-test="price-min"
        />

        <input
            type="range"
            :min="min"
            :max="max"
            :step="step"
            x-model.number="high"
            @input="clampHigh()"
            :aria-valuetext="money(high)"
            aria-label="{{ __('Maximum price') }}"
            data-test="price-max"
        />
    </div>

    {{-- Screen readers get the current span announced when it settles, since the two
         handles individually do not convey the range they describe together. --}}
    <p class="sr-only" aria-live="polite">
        {{ __('Showing gifts priced between') }}
        <span x-text="money(low)"></span>
        {{ __('and') }}
        <span x-text="money(high)"></span>
    </p>
</div>
