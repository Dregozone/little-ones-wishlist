{{--
    Public shell for the wishlist.

    Appearance is settled by partials.appearance rather than hardcoded here: a guest who
    has not chosen gets dark, and the sun/moon switch in the header is theirs to change.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <meta name="robots" content="noindex, nofollow" />
        @include('partials.appearance')
    </head>
    <body class="wl-theme min-h-svh bg-oat-100 text-oat-900 antialiased dark:bg-oat-950 dark:text-oat-100">
        <a
            href="#wishlist-content"
            class="sr-only rounded-md bg-sage-600 px-4 py-2 text-white focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50"
        >
            {{ __('Skip to the list') }}
        </a>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
