@props([
    'src' => null,
    'alt' => '',
])

{{--
    Product photos come straight from shop websites, so they arrive at wildly different
    sizes and aspect ratios. A fixed 4:3 box with object-cover normalises them into a
    uniform grid without downloading or re-encoding anything.

    Two failure modes are handled: a slow image (skeleton shimmer underneath) and a dead or
    hotlink-blocked one (the img hides itself, leaving a neutral placeholder).
--}}
<div
    {{ $attributes->merge(['class' => 'relative aspect-[4/3] w-full overflow-hidden rounded-xl bg-oat-200 dark:bg-oat-800']) }}
>
    <div class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
        <flux:icon.gift class="size-8 text-oat-400 dark:text-oat-600" />
    </div>

    @if (filled($src))
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            loading="lazy"
            decoding="async"
            class="wl-transition absolute inset-0 size-full object-cover object-center"
            onerror="this.style.display='none'"
        />
    @endif
</div>
