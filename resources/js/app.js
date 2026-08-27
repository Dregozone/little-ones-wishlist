/**
 * Alpine behaviour for the wishlist.
 *
 * Flux boots Alpine itself at @fluxScripts, so everything registers against the
 * `alpine:init` event rather than starting Alpine a second time.
 */
document.addEventListener('alpine:init', () => {
    /**
     * Dual-handle price filter.
     *
     * Two real range inputs sit on top of one another so the browser gives us keyboard
     * support, screen reader announcements and native touch handling for free. This only
     * has to keep the two handles from crossing, paint the selected span of the track, and
     * push the result to the server once the guest stops moving.
     */
    window.Alpine.data('priceRange', ({ min, max, low, high, step = 1, onChange }) => ({
        min,
        max,
        step,
        low,
        high,
        timer: null,

        init() {
            this.$watch('low', () => this.schedule())
            this.$watch('high', () => this.schedule())
        },

        /** Keep the lower handle at or below the upper one. */
        clampLow() {
            if (this.low > this.high) {
                this.low = this.high
            }
        },

        /** Keep the upper handle at or above the lower one. */
        clampHigh() {
            if (this.high < this.low) {
                this.high = this.low
            }
        },

        /**
         * Debounce the trip to the server. Dragging a slider fires continuously; filtering
         * on every pixel would flood the connection for no visible benefit.
         */
        schedule() {
            clearTimeout(this.timer)
            this.timer = setTimeout(() => onChange(Number(this.low), Number(this.high)), 350)
        },

        /** Position of a value along the track, as a percentage. */
        percent(value) {
            if (this.max === this.min) {
                return 0
            }

            return ((value - this.min) / (this.max - this.min)) * 100
        },

        /** Inline style for the highlighted portion of the track. */
        get fillStyle() {
            const start = this.percent(this.low)

            return `left: ${start}%; width: ${Math.max(this.percent(this.high) - start, 0)}%`
        },

        /** Money formatting shared by the labels and the accessible value text. */
        money(value) {
            return new Intl.NumberFormat('en-GB', {
                style: 'currency',
                currency: 'GBP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            }).format(value)
        },
    }))

    /**
     * Mirrors the guest's identity token into localStorage as a second chance at
     * recognising them later, and replays it through the recovery route when the cookie
     * has gone but the mirror survived.
     *
     * The cookie remains the primary record — it is HttpOnly and server-set, which is what
     * makes it durable in browsers that cap script-written storage. This is the backup.
     */
    window.Alpine.data('guestMemory', ({ token, restoreUrl }) => ({
        init() {
            const key = 'wl_guest_token'

            try {
                if (token) {
                    localStorage.setItem(key, token)

                    return
                }

                const remembered = localStorage.getItem(key)

                // The guard stops a redirect loop if the token no longer resolves.
                if (remembered && !sessionStorage.getItem('wl_restore_attempted')) {
                    sessionStorage.setItem('wl_restore_attempted', '1')
                    window.location.assign(restoreUrl.replace('__TOKEN__', encodeURIComponent(remembered)))
                }
            } catch (error) {
                // Private browsing modes can throw on storage access. The cookie still works.
            }
        },
    }))

    /**
     * Sun and moon switch.
     *
     * Flux owns the preference itself — `$flux.dark` writes `flux.appearance` and puts the
     * `dark` class on the document. All this adds is keeping the browser's own chrome in
     * step, read straight off the page so the two never drift apart.
     */
    window.Alpine.data('appearanceToggle', () => ({
        toggle() {
            this.$flux.dark = !this.$flux.dark

            this.$nextTick(() => {
                const meta = document.querySelector('meta[name="theme-color"]')

                if (meta) {
                    meta.content = getComputedStyle(document.body).backgroundColor
                }
            })
        },
    }))
})
