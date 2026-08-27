{{--
    Appearance for the standalone pages.

    Flux keeps three states but only ever stores two: an explicit "light" or "dark" under
    `flux.appearance`, with "system" being the absence of a stored value. The list and the
    manager carry a plain sun/moon switch rather than a three-way control, so an unresolved
    "system" is settled here, in favour of dark.

    This has to run in the head, before Flux's own script boots and before the first paint,
    or a device set to light would show a pale flash on its way to dark.
--}}
<meta name="theme-color" content="#1A1815" />

<script>
    (() => {
        let appearance = 'dark';

        try {
            appearance = window.localStorage.getItem('flux.appearance') ?? 'dark';
            window.localStorage.setItem('flux.appearance', appearance);
        } catch (error) {
            // Private browsing can refuse storage entirely. The page still opens in dark.
        }

        window.Flux.applyAppearance(appearance);

        document.querySelector('meta[name="theme-color"]').content =
            appearance === 'dark' ? '#1A1815' : '#FBF8F3';
    })();
</script>
