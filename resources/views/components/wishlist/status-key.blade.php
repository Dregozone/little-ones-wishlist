{{--
    The legend. Status is never carried by colour alone anywhere in this app — each state
    has a swatch, an icon and words — but the key still helps people connect the three
    colours to their meaning at a glance.
--}}
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-oat-700 dark:text-oat-300']) }}>
    <span class="flex items-center gap-1.5">
        <span class="size-2.5 shrink-0 rounded-full bg-status-available" aria-hidden="true"></span>
        {{ __('Available') }}
    </span>

    <span class="flex items-center gap-1.5">
        <span class="size-2.5 shrink-0 rounded-full bg-status-taken" aria-hidden="true"></span>
        {{ __('Someone is getting this') }}
    </span>

    <span class="flex items-center gap-1.5">
        <span class="size-2.5 shrink-0 rounded-full bg-status-yours" aria-hidden="true"></span>
        {{ __("You're getting this") }}
    </span>
</div>
