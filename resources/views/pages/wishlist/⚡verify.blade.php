<?php

use App\Actions\VerifyParentNames;
use App\Http\Middleware\EnsureGuestHasVerified;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::wishlist')]
#[Title('Who is this list for?')]
class extends Component {
    public string $answer = '';

    /**
     * Check the answer and, if it identifies the family, let the guest through.
     */
    public function verify(VerifyParentNames $verify): void
    {
        $key = 'wishlist-gate:'.request()->ip();

        // The gate is a nuisance filter rather than a security boundary, but there is no
        // reason to let a script sit and grind through names either.
        if (RateLimiter::tooManyAttempts($key, maxAttempts: 10)) {
            $this->addError('answer', __('That is a lot of tries. Give it a minute and have another go.'));

            return;
        }

        RateLimiter::hit($key, decaySeconds: 60);

        $this->validate([
            'answer' => ['required', 'string', 'max:120'],
        ], attributes: [
            'answer' => __('answer'),
        ]);

        if (! $verify($this->answer)) {
            $this->addError('answer', __('Not quite. We need a first name of one of the parents, or the family surname.'));

            return;
        }

        RateLimiter::clear($key);

        session()->put(EnsureGuestHasVerified::SESSION_KEY, true);

        Cookie::queue(Cookie::make(
            name: EnsureGuestHasVerified::COOKIE,
            value: '1',
            minutes: EnsureGuestHasVerified::LIFETIME,
            httpOnly: true,
            sameSite: 'lax',
        ));

        $this->redirectRoute('home', navigate: false);
    }
}; ?>

<div class="flex min-h-svh items-center justify-center px-4 py-12">
    <div class="wl-rise w-full max-w-md">
        <div class="rounded-3xl border border-oat-200 bg-oat-50 p-6 shadow-lg shadow-oat-900/5 sm:p-8 dark:border-oat-800 dark:bg-oat-900 dark:shadow-black/30">
            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-sage-700 dark:text-sage-300">
                {{ __('Baby Learmonth') }}
            </p>

            <flux:heading size="xl" level="1" class="mt-2 text-oat-900 dark:text-oat-50">
                {{ __('Who is this baby list for?') }}
            </flux:heading>

            <form wire:submit="verify" class="mt-6 space-y-4">
                <flux:input
                    wire:model="answer"
                    :label="__('Their name')"
                    :placeholder="__('A first name is plenty')"
                    class="min-h-11"
                    autofocus
                    autocomplete="off"
                    required
                    data-test="gate-answer"
                />

                <flux:button
                    type="submit"
                    variant="primary"
                    class="min-h-11 w-full"
                    data-test="gate-submit"
                >
                    <span wire:loading.remove wire:target="verify">{{ __('Show me the list') }}</span>
                    <span wire:loading wire:target="verify">{{ __('Checking...') }}</span>
                </flux:button>
            </form>

            <p class="mt-5 text-xs/relaxed text-oat-600 dark:text-oat-400">
                {{ __('Sorry to ask. This is a public web address, so one small question keeps the list from being scribbled on by anyone who stumbles across it. Anyone who knows the family will get it first time, and you will only ever be asked once on this device.') }}
            </p>
        </div>
    </div>
</div>
