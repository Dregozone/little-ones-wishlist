<?php

use App\Actions\ManageCookieConsent;
use Livewire\Component;

new class extends Component {
    public bool $hasDecided = false;

    /**
     * Work out whether this guest still owes us an answer about cookies.
     */
    public function mount(ManageCookieConsent $consent): void
    {
        $this->hasDecided = $consent->hasDecided(request());
    }

    /**
     * Record the guest's decision and let the list know it may have changed.
     */
    public function decide(bool $accepted, ManageCookieConsent $consent): void
    {
        $consent->store(request(), $accepted);

        $this->hasDecided = true;

        $this->dispatch('consent-updated');
    }
}; ?>

<div>
    @unless ($hasDecided)
        {{-- A region rather than a dialog: trapping focus in a banner the guest is
             allowed to ignore would strand keyboard users on the page. --}}
        <section
            aria-labelledby="cookie-consent-heading"
            class="fixed inset-x-0 bottom-0 z-40 p-3 sm:p-4"
        >
            <div
                class="wl-rise mx-auto max-w-2xl rounded-2xl border border-oat-300 bg-oat-50 p-4 shadow-lg shadow-oat-900/10 sm:p-5 dark:border-oat-800 dark:bg-oat-900 dark:shadow-black/40"
            >
                <flux:heading id="cookie-consent-heading" size="lg" class="text-oat-900 dark:text-oat-100">
                    {{ __('A quick word about cookies') }}
                </flux:heading>

                <div class="mt-2 space-y-2 text-sm/relaxed text-oat-700 dark:text-oat-300">
                    <p>
                        {{ __("We'd like to keep one small cookie on your device. It remembers which items you've said you'll buy, so that when you come back we can show you what you've already committed to and you don't accidentally buy twice.") }}
                    </p>
                    <p>
                        {{ __('Emma and Anders are only ever told that an item has been claimed — never by whom. Your choices stay anonymous to them and to every other guest, so any surprises you have planned stay surprises.') }}
                    </p>
                    <p class="text-oat-600 dark:text-oat-400">
                        {{ __("You can say no and still use the list normally. Your claim will still stop anyone else buying the same thing — we just won't be able to recognise you when you return.") }}
                    </p>
                </div>

                <div class="mt-4 flex flex-col gap-2 sm:flex-row-reverse sm:justify-start">
                    <flux:button
                        variant="primary"
                        class="min-h-11 w-full sm:w-auto"
                        wire:click="decide(true)"
                        data-test="accept-cookies"
                    >
                        {{ __('Yes, remember me') }}
                    </flux:button>

                    <flux:button
                        variant="ghost"
                        class="min-h-11 w-full sm:w-auto"
                        wire:click="decide(false)"
                        data-test="decline-cookies"
                    >
                        {{ __('No thanks') }}
                    </flux:button>
                </div>
            </div>
        </section>
    @endunless
</div>
