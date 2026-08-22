<?php

use App\Actions\ClaimWishlistItem;
use App\Actions\ManageCookieConsent;
use App\Actions\ReleaseWishlistItem;
use App\Actions\ResolveGuest;
use App\Models\Guest;
use App\Models\WishlistItem;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts::wishlist')]
#[Title('The Wish List')]
class extends Component {
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'min', except: null)]
    public ?int $minPrice = null;

    #[Url(as: 'max', except: null)]
    public ?int $maxPrice = null;

    /** The item awaiting a "let someone else get this?" confirmation. */
    public ?int $releasing = null;

    /** Set when the guest dismisses the offer to restore a list seen from this connection. */
    public bool $identityPromptDismissed = false;

    /** Set when the guest has seen and put away their personal recovery link. */
    public bool $recoveryCardDismissed = false;

    /**
     * Seed the price filter from the real bounds of the list, and raise any message
     * flashed by a plain redirect such as the recovery link.
     */
    public function mount(): void
    {
        $this->minPrice ??= $this->bounds['min'];
        $this->maxPrice ??= $this->bounds['max'];

        /** @var array{variant: string, text: string}|null $flashed */
        $flashed = session()->pull('wishlist.toast');

        if ($flashed !== null) {
            Flux::toast(variant: $flashed['variant'], text: $flashed['text']);
        }
    }

    /**
     * The anonymous guest behind this request, if we recognise them at all.
     */
    #[Computed]
    public function guest(): ?Guest
    {
        return app(ResolveGuest::class)(request());
    }

    /**
     * Whether this guest has agreed to being remembered between visits.
     */
    #[Computed]
    public function hasConsented(): bool
    {
        return app(ManageCookieConsent::class)->hasAccepted(request());
    }

    /**
     * The cheapest and dearest items on the list, in whole pounds, used as slider bounds.
     *
     * @return array{min: int, max: int}
     */
    #[Computed]
    public function bounds(): array
    {
        /** @var object{low: int|null, high: int|null}|null $range */
        $range = WishlistItem::query()
            ->visible()
            ->selectRaw('MIN(price_pennies) as low, MAX(price_pennies) as high')
            ->first();

        return [
            'min' => (int) floor(((int) ($range->low ?? 0)) / 100),
            'max' => max((int) ceil(((int) ($range->high ?? 0)) / 100), 1),
        ];
    }

    /**
     * Progress across the whole list, deliberately ignoring the filters: the headline is
     * "how much of this is sorted", not "how much of what you happen to be looking at".
     *
     * @return array{total: int, claimed: int, percent: int}
     */
    #[Computed]
    public function totals(): array
    {
        $total = WishlistItem::query()->visible()->count();
        $claimed = WishlistItem::query()->visible()->has('claim')->count();

        return [
            'total' => $total,
            'claimed' => $claimed,
            'percent' => $total > 0 ? (int) round(($claimed / $total) * 100) : 0,
        ];
    }

    /**
     * The filtered items, split into the three states the key describes.
     *
     * @return array{available: Collection<int, WishlistItem>, yours: Collection<int, WishlistItem>, taken: Collection<int, WishlistItem>}
     */
    #[Computed]
    public function groups(): array
    {
        $guestId = $this->guest?->id;

        $items = WishlistItem::query()
            ->visible()
            ->matching($this->search)
            ->whereBetween('price_pennies', [
                ($this->minPrice ?? $this->bounds['min']) * 100,
                ($this->maxPrice ?? $this->bounds['max']) * 100,
            ])
            ->with('claim')
            ->orderBy('name')
            ->get();

        return [
            'available' => $items->filter(fn (WishlistItem $item): bool => $item->claim === null)->values(),
            'yours' => $items->filter(fn (WishlistItem $item): bool => $item->claim !== null && $item->claim->guest_id === $guestId)->values(),
            'taken' => $items->filter(fn (WishlistItem $item): bool => $item->claim !== null && $item->claim->guest_id !== $guestId)->values(),
        ];
    }

    /**
     * A list claimed from this same connection, offered for the guest to confirm.
     */
    #[Computed]
    public function identityCandidate(): ?Guest
    {
        if ($this->guest !== null || $this->identityPromptDismissed || ! $this->hasConsented) {
            return null;
        }

        return app(ResolveGuest::class)->candidateFromIp(request());
    }

    /**
     * The guest's personal recovery link, once they have something worth recovering.
     */
    #[Computed]
    public function recoveryUrl(): ?string
    {
        $guest = $this->guest;

        if ($guest === null || ! $guest->has_consented || $this->recoveryCardDismissed) {
            return null;
        }

        if ($guest->claims()->count() === 0) {
            return null;
        }

        $token = app(ResolveGuest::class)->token(request());

        return $token === null ? null : route('wishlist.restore', ['token' => $token]);
    }

    /**
     * The identity token this device holds, mirrored into localStorage as a second chance
     * at recognising the guest if the cookie is ever cleared.
     */
    #[Computed]
    public function memoryToken(): ?string
    {
        return $this->guest?->has_consented
            ? app(ResolveGuest::class)->token(request())
            : null;
    }

    /**
     * Apply a new price window, called by the slider once the guest stops dragging.
     */
    public function setPriceRange(int $low, int $high): void
    {
        $this->minPrice = max($low, $this->bounds['min']);
        $this->maxPrice = min($high, $this->bounds['max']);

        unset($this->groups);
    }

    /**
     * Clear every filter back to showing the whole list.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->minPrice = $this->bounds['min'];
        $this->maxPrice = $this->bounds['max'];

        unset($this->groups);
    }

    /**
     * Commit this guest to buying an item.
     */
    public function claim(int $itemId, ClaimWishlistItem $claim, ResolveGuest $resolve): void
    {
        $item = WishlistItem::query()->visible()->find($itemId);

        if ($item === null) {
            return;
        }

        $guest = $resolve->orCreate(request(), $this->hasConsented);

        unset($this->guest);

        if (! $claim($item, $guest)) {
            $this->refreshLists();

            Flux::toast(
                variant: 'warning',
                text: __('Someone else claimed that one just now, so it is already taken care of.'),
            );

            return;
        }

        $this->refreshLists();

        Flux::toast(
            variant: 'success',
            text: __(':item, you are getting this one.', ['item' => $item->name]),
        );
    }

    /**
     * Ask before letting go of a commitment, so a mis-tap is recoverable.
     */
    public function confirmRelease(int $itemId): void
    {
        $this->releasing = $itemId;

        Flux::modal('confirm-release')->show();
    }

    /**
     * Hand an item back to the list.
     */
    public function release(ReleaseWishlistItem $release): void
    {
        $item = $this->releasing === null ? null : WishlistItem::find($this->releasing);
        $guest = $this->guest;

        $this->releasing = null;

        Flux::modal('confirm-release')->close();

        if ($item === null || $guest === null || ! $release($item, $guest)) {
            return;
        }

        $this->refreshLists();

        Flux::toast(
            variant: 'success',
            text: __(':item is back on the list for someone else.', ['item' => $item->name]),
        );
    }

    /**
     * Take on the list previously claimed from this connection, after the guest confirms
     * that it was in fact them.
     */
    public function adoptIdentity(ResolveGuest $resolve): void
    {
        $candidate = $this->identityCandidate;

        if ($candidate === null) {
            return;
        }

        // A brand new token is minted rather than the old one reused, so the guest's other
        // devices and any recovery link they saved keep working alongside this one.
        $resolve->issueToken(request(), $candidate);

        $this->identityPromptDismissed = true;

        unset($this->guest);

        $this->refreshLists();

        Flux::toast(variant: 'success', text: __('Welcome back, your picks are highlighted again.'));
    }

    /**
     * Put away the "was this you?" prompt without acting on it.
     */
    public function dismissIdentityPrompt(): void
    {
        $this->identityPromptDismissed = true;
    }

    /**
     * Put away the personal recovery link card.
     */
    public function dismissRecoveryCard(): void
    {
        $this->recoveryCardDismissed = true;
    }

    /**
     * Rebuild the list when the guest decides about cookies, since consenting mid-visit
     * changes whether we can recognise them.
     */
    #[On('consent-updated')]
    public function refreshLists(): void
    {
        unset(
            $this->groups,
            $this->totals,
            $this->hasConsented,
            $this->identityCandidate,
            $this->recoveryUrl,
            $this->memoryToken,
        );
    }
}; ?>

<div
    class="mx-auto w-full max-w-6xl px-4 pb-28 sm:px-6"
    x-data="guestMemory({
        token: @js($this->memoryToken),
        restoreUrl: @js(route('wishlist.restore', ['token' => '__TOKEN__'])),
    })"
>
    @include('partials.wishlist-header')

    <main id="wishlist-content" class="mt-8 space-y-12">
        {{-- Screen readers are told the running total; sighted guests read the bar. --}}
        <p class="sr-only" aria-live="polite">
            {{ __(':claimed of :total gifts are spoken for.', ['claimed' => $this->totals['claimed'], 'total' => $this->totals['total']]) }}
        </p>

        @if ($this->identityCandidate)
            <flux:callout variant="secondary" icon="sparkles" class="wl-rise">
                <flux:callout.heading>{{ __('Have you been here before?') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Somebody using this internet connection has already picked out :count gifts. If that was you, we can highlight them again.', ['count' => $this->identityCandidate->claims()->count()]) }}
                </flux:callout.text>
                <x-slot name="actions">
                    <flux:button size="sm" variant="primary" wire:click="adoptIdentity" data-test="adopt-identity">
                        {{ __('Yes, that was me') }}
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="dismissIdentityPrompt" data-test="dismiss-identity">
                        {{ __('No, that was someone else') }}
                    </flux:button>
                </x-slot>
            </flux:callout>
        @endif

        @if ($this->recoveryUrl)
            <flux:callout variant="success" icon="bookmark" class="wl-rise">
                <flux:callout.heading>{{ __('Keep your link') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Save this somewhere: message it to yourself, or bookmark it. Opening it on any phone or computer brings your picks back, even if this browser forgets you.') }}
                </flux:callout.text>

                <flux:input
                    class="mt-3"
                    :value="$this->recoveryUrl"
                    readonly
                    copyable
                    data-test="recovery-link"
                    :label="__('Your personal link')"
                />

                <x-slot name="actions">
                    <flux:button size="sm" variant="ghost" wire:click="dismissRecoveryCard" data-test="dismiss-recovery">
                        {{ __('Got it') }}
                    </flux:button>
                </x-slot>
            </flux:callout>
        @endif

        @php
            $groups = $this->groups;
            $isEmpty = $groups['available']->isEmpty() && $groups['yours']->isEmpty() && $groups['taken']->isEmpty();
        @endphp

        @if ($isEmpty)
            <div class="rounded-2xl border border-dashed border-oat-300 p-10 text-center dark:border-oat-800">
                <flux:heading size="lg">{{ __('Nothing matches that') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Try a different word, or widen the price range.') }}</flux:text>
                <flux:button class="mt-4 min-h-11" variant="primary" wire:click="clearFilters" data-test="clear-filters">
                    {{ __('Show everything') }}
                </flux:button>
            </div>
        @endif

        <x-wishlist.section
            :title="__('Still available')"
            :count="$groups['available']->count()"
            tone="available"
            :open="true"
        >
            @foreach ($groups['available'] as $index => $item)
                <x-wishlist.item-card :item="$item" state="available" :index="$index">
                    <flux:button
                        variant="primary"
                        class="min-h-11 w-full"
                        wire:click="claim({{ $item->id }})"
                        wire:loading.attr="disabled"
                        wire:target="claim({{ $item->id }})"
                        aria-pressed="false"
                        data-test="claim-{{ $item->id }}"
                    >
                        <span wire:loading.remove wire:target="claim({{ $item->id }})">{{ __('I will get this') }}</span>
                        <span wire:loading wire:target="claim({{ $item->id }})">{{ __('Saving...') }}</span>
                    </flux:button>
                </x-wishlist.item-card>
            @endforeach
        </x-wishlist.section>

        <x-wishlist.section
            :title="__('You are getting')"
            :count="$groups['yours']->count()"
            tone="yours"
            :open="true"
        >
            @foreach ($groups['yours'] as $index => $item)
                <x-wishlist.item-card :item="$item" state="yours" :index="$index">
                    <flux:button
                        variant="ghost"
                        class="min-h-11 w-full"
                        wire:click="confirmRelease({{ $item->id }})"
                        aria-pressed="true"
                        data-test="release-{{ $item->id }}"
                    >
                        {{ __('Change my mind') }}
                    </flux:button>
                </x-wishlist.item-card>
            @endforeach
        </x-wishlist.section>

        <x-wishlist.section
            :title="__('Spoken for')"
            :count="$groups['taken']->count()"
            tone="taken"
            :open="false"
            :description="__('Already sorted by another guest, so there is no need to buy these.')"
        >
            @foreach ($groups['taken'] as $index => $item)
                <x-wishlist.item-card :item="$item" state="taken" :index="$index">
                    <flux:button variant="ghost" class="min-h-11 w-full" disabled aria-pressed="false">
                        {{ __('Already taken') }}
                    </flux:button>
                </x-wishlist.item-card>
            @endforeach
        </x-wishlist.section>
    </main>

    {{-- Asked here rather than in the layout: the gate already asks one thing of a first
         time visitor, and stacking a cookie banner on top of it is a lot to greet someone
         with. By the list they are about to claim, which is when the cookie matters. --}}
    <x-cookie-consent />

    <flux:modal name="confirm-release" class="max-w-sm">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Let someone else get this?') }}</flux:heading>
            <flux:text>
                {{ __('It goes straight back on the list as available. You can always pick it up again if nobody else does.') }}
            </flux:text>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" class="min-h-11 w-full sm:w-auto">{{ __('Keep it') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" class="min-h-11 w-full sm:w-auto" wire:click="release" data-test="confirm-release">
                    {{ __('Put it back') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
