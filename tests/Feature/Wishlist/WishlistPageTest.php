<?php

use App\Http\Middleware\EnsureGuestHasVerified;
use App\Models\Guest;
use App\Models\WishlistItem;
use Livewire\Livewire;

beforeEach(function () {
    session()->put(EnsureGuestHasVerified::SESSION_KEY, true);
});

test('the list renders for a verified guest', function () {
    WishlistItem::factory()->create(['name' => 'Cot Mobile']);

    $this->withCookie(EnsureGuestHasVerified::COOKIE, '1')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Cot Mobile');
});

test('search matches the item name', function () {
    WishlistItem::factory()->create(['name' => 'Cot Mobile']);
    WishlistItem::factory()->create(['name' => 'Play Mat']);

    $groups = Livewire::test('pages::wishlist.index')
        ->set('search', 'mobile')
        ->get('groups');

    expect($groups['available']->pluck('name')->all())->toBe(['Cot Mobile']);
});

test('search matches the shop name', function () {
    WishlistItem::factory()->create(['name' => 'Cot Mobile', 'shop_name' => 'JoJo Maman']);
    WishlistItem::factory()->create(['name' => 'Play Mat', 'shop_name' => 'IKEA']);

    $groups = Livewire::test('pages::wishlist.index')
        ->set('search', 'ikea')
        ->get('groups');

    expect($groups['available']->pluck('name')->all())->toBe(['Play Mat']);
});

test('search matches the description', function () {
    WishlistItem::factory()->create(['name' => 'Cot Mobile', 'description' => 'Soft felt stars']);
    WishlistItem::factory()->create(['name' => 'Play Mat', 'description' => 'Interlocking foam']);

    $groups = Livewire::test('pages::wishlist.index')
        ->set('search', 'foam')
        ->get('groups');

    expect($groups['available']->pluck('name')->all())->toBe(['Play Mat']);
});

test('the price range narrows the list', function () {
    WishlistItem::factory()->create(['name' => 'Cheap', 'price_pennies' => 500]);
    WishlistItem::factory()->create(['name' => 'Middling', 'price_pennies' => 2500]);
    WishlistItem::factory()->create(['name' => 'Dear', 'price_pennies' => 9000]);

    $groups = Livewire::test('pages::wishlist.index')
        ->call('setPriceRange', 20, 40)
        ->get('groups');

    expect($groups['available']->pluck('name')->all())->toBe(['Middling']);
});

test('the price range is clamped to the real bounds of the list', function () {
    WishlistItem::factory()->create(['price_pennies' => 1000]);
    WishlistItem::factory()->create(['price_pennies' => 5000]);

    $component = Livewire::test('pages::wishlist.index')->call('setPriceRange', -50, 9999);

    expect($component->get('minPrice'))->toBe(10)
        ->and($component->get('maxPrice'))->toBe(50);
});

test('clearing the filters restores the whole list', function () {
    WishlistItem::factory()->count(3)->create(['price_pennies' => 1000]);

    $groups = Livewire::test('pages::wishlist.index')
        ->set('search', 'nothing matches this')
        ->call('clearFilters')
        ->get('groups');

    expect($groups['available'])->toHaveCount(3);
});

test('hidden items are kept from guests', function () {
    WishlistItem::factory()->create(['name' => 'Visible thing']);
    WishlistItem::factory()->hidden()->create(['name' => 'Secret thing']);

    $groups = Livewire::test('pages::wishlist.index')->get('groups');

    expect($groups['available']->pluck('name')->all())->toBe(['Visible thing']);
});

test('progress counts the whole list, not the filtered view', function () {
    WishlistItem::factory()->count(3)->create(['name' => 'Plain thing']);
    WishlistItem::factory()->claimed()->create(['name' => 'Taken thing']);

    $totals = Livewire::test('pages::wishlist.index')
        ->set('search', 'nothing matches this')
        ->get('totals');

    expect($totals)->toBe(['total' => 4, 'claimed' => 1, 'percent' => 25]);
});

test('progress copes with an empty list', function () {
    $totals = Livewire::test('pages::wishlist.index')->get('totals');

    expect($totals)->toBe(['total' => 0, 'claimed' => 0, 'percent' => 0]);
});

test('items claimed by other guests are shown as taken, never as yours', function () {
    WishlistItem::factory()->claimed()->create(['name' => 'Taken thing']);

    $groups = Livewire::test('pages::wishlist.index')->get('groups');

    expect($groups['taken']->pluck('name')->all())->toBe(['Taken thing'])
        ->and($groups['yours'])->toBeEmpty()
        ->and($groups['available'])->toBeEmpty();
});

test('the page never leaks who claimed an item', function () {
    $guest = Guest::factory()->withToken('a-secret-guest-token-value')->create();
    WishlistItem::factory()->claimed($guest)->create(['name' => 'Taken thing']);

    $tokenHash = $guest->tokens()->value('token_hash');

    expect($tokenHash)->not->toBeNull();

    $response = $this->withCookie(EnsureGuestHasVerified::COOKIE, '1')->get(route('home'));

    $response->assertOk()->assertSee('Taken thing');

    // Guests were promised that nobody learns who claimed what. Neither the guest's id,
    // nor their device token, nor its digest may appear anywhere in the page.
    expect($response->getContent())
        ->not->toContain((string) $tokenHash)
        ->not->toContain('a-secret-guest-token-value')
        ->not->toContain('guest_id');
});

test('the list carries a light and dark switch that opens dark by default', function () {
    $content = $this->withCookie(EnsureGuestHasVerified::COOKIE, '1')
        ->get(route('home'))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('data-test="appearance-toggle"')
        // An unresolved "system" is settled in favour of dark before the first paint.
        ->toContain("window.localStorage.getItem('flux.appearance') ?? 'dark'");
});
