<?php

use App\Actions\ManageCookieConsent;
use App\Actions\ResolveGuest;
use App\Http\Middleware\EnsureGuestHasVerified;
use App\Models\Guest;
use App\Models\WishlistClaim;
use App\Models\WishlistItem;
use Livewire\Livewire;

beforeEach(function () {
    session()->put(EnsureGuestHasVerified::SESSION_KEY, true);
    session()->put('wl_consent', true);
});

test('a guest can claim an item', function () {
    $item = WishlistItem::factory()->create();

    $groups = Livewire::test('pages::wishlist.index')
        ->call('claim', $item->id)
        ->get('groups');

    expect(WishlistClaim::where('wishlist_item_id', $item->id)->exists())->toBeTrue()
        ->and($groups['yours']->pluck('id')->all())->toBe([$item->id])
        ->and($groups['available'])->toBeEmpty();
});

test('claiming creates a guest record only once', function () {
    $items = WishlistItem::factory()->count(2)->create();

    Livewire::test('pages::wishlist.index')
        ->call('claim', $items[0]->id)
        ->call('claim', $items[1]->id);

    expect(Guest::count())->toBe(1)
        ->and(WishlistClaim::count())->toBe(2);
});

test('an item already claimed by somebody else cannot be taken', function () {
    $item = WishlistItem::factory()->claimed()->create();
    $originalClaim = $item->claim;

    Livewire::test('pages::wishlist.index')->call('claim', $item->id);

    expect(WishlistClaim::where('wishlist_item_id', $item->id)->count())->toBe(1)
        ->and($item->claim()->first()->guest_id)->toBe($originalClaim->guest_id);
});

test('a hidden item cannot be claimed', function () {
    $item = WishlistItem::factory()->hidden()->create();

    Livewire::test('pages::wishlist.index')->call('claim', $item->id);

    expect(WishlistClaim::count())->toBe(0);
});

test('a guest can release their own claim', function () {
    $item = WishlistItem::factory()->create();

    $component = Livewire::test('pages::wishlist.index')->call('claim', $item->id);

    expect(WishlistClaim::count())->toBe(1);

    $groups = $component
        ->call('confirmRelease', $item->id)
        ->call('release')
        ->get('groups');

    expect(WishlistClaim::count())->toBe(0)
        ->and($groups['available']->pluck('id')->all())->toBe([$item->id])
        ->and($groups['yours'])->toBeEmpty();
});

test('a guest cannot release a claim belonging to somebody else', function () {
    $item = WishlistItem::factory()->claimed()->create();
    $mine = WishlistItem::factory()->create();

    // Claim something so that this visitor has an identity of their own, then aim the
    // release at an item belonging to a different guest.
    Livewire::test('pages::wishlist.index')
        ->call('claim', $mine->id)
        ->set('releasing', $item->id)
        ->call('release');

    expect($item->claim()->exists())->toBeTrue();
});

test('releasing without an identity does nothing', function () {
    $item = WishlistItem::factory()->claimed()->create();

    Livewire::test('pages::wishlist.index')
        ->set('releasing', $item->id)
        ->call('release');

    expect($item->claim()->exists())->toBeTrue();
});

test('a returning guest is recognised by their cookie', function () {
    $item = WishlistItem::factory()->create();
    $guest = Guest::factory()->withToken('rememberedTokenForThisGuest0123456789abcd')->create();

    WishlistClaim::create(['wishlist_item_id' => $item->id, 'guest_id' => $guest->id]);

    $this->withCookie(ResolveGuest::COOKIE, 'rememberedTokenForThisGuest0123456789abcd')
        ->withCookie(EnsureGuestHasVerified::COOKIE, '1')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('You are getting');
});

test('the recovery link restores a guest on another device', function () {
    $item = WishlistItem::factory()->create();
    $guest = Guest::factory()->withToken('recoveryTokenForThisGuest0123456789abcdef')->create();

    WishlistClaim::create(['wishlist_item_id' => $item->id, 'guest_id' => $guest->id]);

    $this->get(route('wishlist.restore', ['token' => 'recoveryTokenForThisGuest0123456789abcdef']))
        ->assertRedirect(route('home'))
        ->assertCookie(ResolveGuest::COOKIE);

    // A second token is minted rather than the first replaced, so the original device and
    // the saved link both keep working.
    expect($guest->tokens()->count())->toBe(2);
});

test('an unknown recovery token is turned away without an identity', function () {
    $this->get(route('wishlist.restore', ['token' => str_repeat('z', 40)]))
        ->assertRedirect(route('home'))
        ->assertCookieMissing(ResolveGuest::COOKIE);

    expect(Guest::count())->toBe(0);
});

test('following a recovery link counts as consenting', function () {
    $guest = Guest::factory()->withoutConsent()->withToken('recoveryTokenForThisGuest0123456789abcdef')->create();

    $this->get(route('wishlist.restore', ['token' => 'recoveryTokenForThisGuest0123456789abcdef']));

    expect($guest->refresh()->has_consented)->toBeTrue();
});

test('a guest who declined the cookie can still claim, but is not given one', function () {
    session()->put('wl_consent', false);

    $item = WishlistItem::factory()->create();

    Livewire::test('pages::wishlist.index')->call('claim', $item->id);

    expect(WishlistClaim::where('wishlist_item_id', $item->id)->exists())->toBeTrue()
        ->and(Guest::first()->has_consented)->toBeFalse();
});

test('a guest who declined the cookie is never offered a recovery link', function () {
    session()->put('wl_consent', false);

    $item = WishlistItem::factory()->create();

    $component = Livewire::test('pages::wishlist.index')->call('claim', $item->id);

    expect($component->get('recoveryUrl'))->toBeNull()
        ->and($component->get('memoryToken'))->toBeNull();
});

test('a recovery link is offered once a consenting guest has claimed something', function () {
    $item = WishlistItem::factory()->create();

    $component = Livewire::test('pages::wishlist.index')->call('claim', $item->id);

    expect($component->get('recoveryUrl'))->toStartWith(url('/r/'));
});

test('the identity prompt is offered only to an unrecognised visitor sharing an IP', function () {
    $item = WishlistItem::factory()->create();
    $guest = Guest::factory()->fromIp('127.0.0.1')->create();

    WishlistClaim::create(['wishlist_item_id' => $item->id, 'guest_id' => $guest->id]);

    $component = Livewire::test('pages::wishlist.index');

    expect($component->get('identityCandidate')?->id)->toBe($guest->id);
});

test('confirming the identity prompt hands back the earlier list', function () {
    $item = WishlistItem::factory()->create();
    $guest = Guest::factory()->fromIp('127.0.0.1')->create();

    WishlistClaim::create(['wishlist_item_id' => $item->id, 'guest_id' => $guest->id]);

    $groups = Livewire::test('pages::wishlist.index')
        ->call('adoptIdentity')
        ->get('groups');

    expect($groups['yours']->pluck('id')->all())->toBe([$item->id])
        ->and($guest->tokens()->count())->toBe(1);
});

test('declining the identity prompt leaves the earlier list alone', function () {
    $item = WishlistItem::factory()->create();
    $guest = Guest::factory()->fromIp('127.0.0.1')->create();

    WishlistClaim::create(['wishlist_item_id' => $item->id, 'guest_id' => $guest->id]);

    $component = Livewire::test('pages::wishlist.index')->call('dismissIdentityPrompt');

    expect($component->get('identityCandidate'))->toBeNull()
        ->and($component->get('groups')['taken']->pluck('id')->all())->toBe([$item->id])
        ->and($guest->tokens()->count())->toBe(0);
});

test('a guest already recognised is never shown the identity prompt', function () {
    $item = WishlistItem::factory()->create();
    $other = Guest::factory()->fromIp('127.0.0.1')->create();

    WishlistClaim::create(['wishlist_item_id' => $item->id, 'guest_id' => $other->id]);

    $mine = WishlistItem::factory()->create();

    $component = Livewire::test('pages::wishlist.index')->call('claim', $mine->id);

    expect($component->get('identityCandidate'))->toBeNull();
});

test('a guest who declined the cookie is never shown the identity prompt', function () {
    session()->put('wl_consent', false);

    $item = WishlistItem::factory()->create();
    $guest = Guest::factory()->fromIp('127.0.0.1')->create();

    WishlistClaim::create(['wishlist_item_id' => $item->id, 'guest_id' => $guest->id]);

    expect(Livewire::test('pages::wishlist.index')->get('identityCandidate'))->toBeNull();
});

test('the consent banner disappears once a choice is made', function () {
    session()->forget('wl_consent');

    $component = Livewire::test('pages::wishlist.consent');

    expect($component->get('hasDecided'))->toBeFalse();

    $component->call('decide', true)
        ->assertDispatched('consent-updated');

    expect($component->get('hasDecided'))->toBeTrue()
        ->and(app(ManageCookieConsent::class)->hasAccepted(request()))->toBeTrue();
});
