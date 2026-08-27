{{--
    Sun and moon switch for the standalone pages.

    The icon is swapped by the `dark` class rather than by Alpine state, so the correct one
    is on screen from the first paint instead of appearing once Alpine has booted. It shows
    the mode the button moves you to, which is what makes a single-icon switch readable.
--}}
<button
    type="button"
    x-data="appearanceToggle"
    x-on:click="toggle"
    aria-label="{{ __('Switch between light and dark mode') }}"
    {{ $attributes->class([
        'wl-transition flex size-11 shrink-0 items-center justify-center rounded-full text-oat-700',
        'hover:bg-oat-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sage-600',
        'dark:text-oat-300 dark:hover:bg-oat-800',
    ]) }}
    data-test="appearance-toggle"
>
    <flux:icon.moon class="size-5 dark:hidden" />
    <flux:icon.sun class="hidden size-5 dark:block" />
</button>
