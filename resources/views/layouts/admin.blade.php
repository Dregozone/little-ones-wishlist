{{--
    Standalone shell for the admin.

    The manager is one page, not an app with sections, so it gets the same full-width
    treatment as the public list rather than the starter kit's sidebar. It wears the
    wishlist's oat and sage palette so that flicking between the two sides of the same
    list does not feel like moving between two products, and like the public shell it
    follows whatever appearance the device asks for.
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
            href="#admin-content"
            class="sr-only rounded-md bg-sage-600 px-4 py-2 text-white focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50"
        >
            {{ __('Skip to the items') }}
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
