{{--
    Sticky progress header for the public list.

    The bar reports the whole list rather than the filtered view, so it always answers
    "how much of this is sorted?" no matter what the guest is searching for.
--}}
{{-- The wrapper carries the Alpine scope so that the sentinel, which must sit in normal
     flow above the sticky element, shares $refs with the header that observes it. --}}
<div
    x-data="{ condensed: false }"
    x-init="
        new IntersectionObserver(
            ([entry]) => (condensed = !entry.isIntersecting),
            { threshold: 0 },
        ).observe($refs.sentinel)
    "
>
    <div x-ref="sentinel" class="h-px" aria-hidden="true"></div>

    <header class="sticky top-0 z-30 -mx-4 px-4 pt-4 sm:-mx-6 sm:px-6">
    <div
        class="wl-transition rounded-2xl border border-oat-200 bg-oat-50/85 p-4 shadow-sm shadow-oat-900/5 backdrop-blur-md sm:p-6 dark:border-oat-800 dark:bg-oat-900/85 dark:shadow-black/30"
        :class="condensed ? 'sm:p-4' : ''"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="wl-transition overflow-hidden" :class="condensed ? 'max-h-0 opacity-0 sm:max-h-0' : 'max-h-40 opacity-100'">
                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-sage-700 dark:text-sage-300">
                    {{ __('Baby Learmonth') }}
                </p>

                <flux:heading size="xl" level="1" class="mt-1 text-oat-900 dark:text-oat-50">
                    {{ __('The Wish List') }}
                </flux:heading>

                <flux:text class="mt-1 max-w-prose text-oat-700 dark:text-oat-300">
                    {{ __('Everything we need before the baby arrives. Tick anything you would like to buy so nobody doubles up.') }}
                </flux:text>
            </div>

            {{-- Kept outside the block that folds away on scroll, so it stays in reach. --}}
            <x-appearance-toggle class="-me-1 -mt-1" />
        </div>

        <div class="mt-4 flex items-baseline justify-between gap-3">
            <p class="text-sm font-medium text-oat-800 dark:text-oat-200">
                {{ __(':claimed of :total gifts spoken for', ['claimed' => $this->totals['claimed'], 'total' => $this->totals['total']]) }}
            </p>
            <p class="text-sm font-semibold tabular-nums text-sage-700 dark:text-sage-300">
                {{ $this->totals['percent'] }}%
            </p>
        </div>

        <div
            class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-oat-200 dark:bg-oat-800"
            role="progressbar"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ $this->totals['percent'] }}"
            aria-label="{{ __('Gifts spoken for') }}"
        >
            <div
                class="wl-progress-fill h-full rounded-full bg-gradient-to-r from-sage-500 to-sage-400 dark:from-sage-400 dark:to-sage-300"
                style="width: {{ $this->totals['percent'] }}%"
            ></div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_16rem] sm:items-end">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                :label="__('Search')"
                :placeholder="__('Try a shop, an item, or a word')"
                class="min-h-11"
                data-test="wishlist-search"
            />

            <x-wishlist.price-range
                :min="$this->bounds['min']"
                :max="$this->bounds['max']"
                :low="$this->minPrice"
                :high="$this->maxPrice"
            />
        </div>

        <x-wishlist.status-key class="mt-4" />
        </div>
    </header>
</div>
