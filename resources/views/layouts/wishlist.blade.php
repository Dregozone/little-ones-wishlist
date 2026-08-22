{{--
    Public shell for the wishlist.

    Unlike the authenticated shell this does not hardcode `class="dark"`: guests arrive from
    a shared link with no say in the matter, so the page follows whatever their device asks
    for via @fluxAppearance.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <meta name="robots" content="noindex, nofollow" />
        <meta name="theme-color" content="#FBF8F3" media="(prefers-color-scheme: light)" />
        <meta name="theme-color" content="#1A1815" media="(prefers-color-scheme: dark)" />
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
