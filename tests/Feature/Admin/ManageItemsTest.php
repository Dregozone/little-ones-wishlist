<?php

use App\Models\Guest;
use App\Models\User;
use App\Models\WishlistClaim;
use App\Models\WishlistItem;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('the manager needs an account', function () {
    auth()->logout();

    $this->get(route('admin.items'))->assertRedirect(route('login'));
});

test('the manager renders for an admin', function () {
    WishlistItem::factory()->create(['name' => 'Cot Mobile']);

    $this->get(route('admin.items'))->assertOk()->assertSee('Cot Mobile');
});

test('quick add creates an item', function () {
    Livewire::test('pages::admin.items')
        ->set('draft.name', 'Cot Mobile')
        ->set('draft.shop_name', 'JoJo Maman')
        ->set('draft.price', '24.50')
        ->call('addItem')
        ->assertHasNoErrors();

    $item = WishlistItem::sole();

    expect($item->name)->toBe('Cot Mobile')
        ->and($item->shop_name)->toBe('JoJo Maman')
        ->and($item->price_pennies)->toBe(2450)
        ->and($item->is_visible)->toBeTrue();
});

test('quick add clears itself after saving', function () {
    $component = Livewire::test('pages::admin.items')
        ->set('draft.name', 'Cot Mobile')
        ->set('draft.shop_name', 'JoJo Maman')
        ->set('draft.price', '24.50')
        ->call('addItem');

    expect($component->get('draft'))->toBe(['name' => '', 'shop_name' => '', 'price' => '']);
});

test('quick add rejects an incomplete item', function () {
    Livewire::test('pages::admin.items')
        ->set('draft.name', '')
        ->set('draft.shop_name', '')
        ->set('draft.price', '')
        ->call('addItem')
        ->assertHasErrors(['draft.name', 'draft.shop_name', 'draft.price']);

    expect(WishlistItem::count())->toBe(0);
});

test('quick add rejects a negative price', function () {
    Livewire::test('pages::admin.items')
        ->set('draft.name', 'Cot Mobile')
        ->set('draft.shop_name', 'JoJo Maman')
        ->set('draft.price', '-5')
        ->call('addItem')
        ->assertHasErrors('draft.price');
});

test('a cell can be edited in place', function () {
    $item = WishlistItem::factory()->create(['name' => 'Cot Mobile']);

    Livewire::test('pages::admin.items')
        ->call('edit', $item->id, 'name')
        ->set('editingValue', 'Cot Mobile, grey')
        ->call('saveCell')
        ->assertHasNoErrors();

    expect($item->refresh()->name)->toBe('Cot Mobile, grey');
});

test('editing a price cell converts pounds to pennies', function () {
    $item = WishlistItem::factory()->create(['price_pennies' => 1000]);

    Livewire::test('pages::admin.items')
        ->call('edit', $item->id, 'price')
        ->set('editingValue', '19.99')
        ->call('saveCell');

    expect($item->refresh()->price_pennies)->toBe(1999);
});

test('an invalid cell edit is refused and leaves the item alone', function () {
    $item = WishlistItem::factory()->create(['name' => 'Cot Mobile']);

    Livewire::test('pages::admin.items')
        ->call('edit', $item->id, 'name')
        ->set('editingValue', '')
        ->call('saveCell')
        ->assertHasErrors('editingValue');

    expect($item->refresh()->name)->toBe('Cot Mobile');
});

test('an edit can be abandoned', function () {
    $item = WishlistItem::factory()->create(['name' => 'Cot Mobile']);

    $component = Livewire::test('pages::admin.items')
        ->call('edit', $item->id, 'name')
        ->set('editingValue', 'Something else')
        ->call('cancelEdit');

    expect($component->get('editing'))->toBeNull()
        ->and($item->refresh()->name)->toBe('Cot Mobile');
});

test('only the three inline fields can be opened for editing', function () {
    $item = WishlistItem::factory()->create();

    $component = Livewire::test('pages::admin.items')->call('edit', $item->id, 'is_visible');

    expect($component->get('editing'))->toBeNull();
});

test('the details drawer saves the long fields', function () {
    $item = WishlistItem::factory()->create();

    Livewire::test('pages::admin.items')
        ->call('toggleDetails', $item->id)
        ->set('details.product_url', 'https://example.com/cot-mobile')
        ->set('details.image_url', 'https://example.com/cot-mobile.jpg')
        ->set('details.description', 'Soft felt stars.')
        ->call('saveDetails')
        ->assertHasNoErrors();

    $item->refresh();

    expect($item->product_url)->toBe('https://example.com/cot-mobile')
        ->and($item->image_url)->toBe('https://example.com/cot-mobile.jpg')
        ->and($item->description)->toBe('Soft felt stars.');
});

test('the details drawer refuses a link that is not a url', function () {
    $item = WishlistItem::factory()->create(['product_url' => null]);

    Livewire::test('pages::admin.items')
        ->call('toggleDetails', $item->id)
        ->set('details.product_url', 'not a url at all')
        ->call('saveDetails')
        ->assertHasErrors('details.product_url');

    expect($item->refresh()->product_url)->toBeNull();
});

test('emptying a long field stores null rather than an empty string', function () {
    $item = WishlistItem::factory()->create(['description' => 'Something']);

    Livewire::test('pages::admin.items')
        ->call('toggleDetails', $item->id)
        ->set('details.description', '')
        ->call('saveDetails');

    expect($item->refresh()->description)->toBeNull();
});

test('an item can be hidden from guests and shown again', function () {
    $item = WishlistItem::factory()->create();

    $component = Livewire::test('pages::admin.items')->call('toggleVisibility', $item->id);

    expect($item->refresh()->is_visible)->toBeFalse();

    $component->call('toggleVisibility', $item->id);

    expect($item->refresh()->is_visible)->toBeTrue();
});

test('an admin can free up a claim', function () {
    $item = WishlistItem::factory()->claimed()->create();

    Livewire::test('pages::admin.items')->call('releaseClaim', $item->id);

    expect($item->claim()->exists())->toBeFalse();
});

test('an item can be deleted along with its claim', function () {
    $item = WishlistItem::factory()->claimed()->create();

    Livewire::test('pages::admin.items')
        ->call('confirmDelete', $item->id)
        ->call('deleteItem');

    expect(WishlistItem::find($item->id))->toBeNull()
        ->and(WishlistClaim::count())->toBe(0);
});

test('search narrows the manager list', function () {
    WishlistItem::factory()->create(['name' => 'Cot Mobile']);
    WishlistItem::factory()->create(['name' => 'Play Mat']);

    $items = Livewire::test('pages::admin.items')->set('search', 'mobile')->get('items');

    expect($items->pluck('name')->all())->toBe(['Cot Mobile']);
});

test('the manager lists hidden items too', function () {
    WishlistItem::factory()->hidden()->create(['name' => 'Secret thing']);

    $items = Livewire::test('pages::admin.items')->get('items');

    expect($items->pluck('name')->all())->toBe(['Secret thing']);
});

test('the manager shows that an item is claimed but never who claimed it', function () {
    $guest = Guest::factory()->withToken('adminVisibleTokenCheck0123456789abcdefgh')->create();
    $item = WishlistItem::factory()->claimed($guest)->create(['name' => 'Taken thing']);

    $tokenHash = $guest->tokens()->value('token_hash');

    $content = $this->get(route('admin.items'))->assertOk()->getContent();

    // The parents were promised they would learn only that something is spoken for. That
    // promise has to hold in the manager as much as on the public list.
    expect($content)->toContain('Claimed')
        ->not->toContain((string) $tokenHash)
        ->not->toContain('adminVisibleTokenCheck0123456789abcdefgh')
        ->not->toContain('guest_id');

    expect($item->claim)->not->toBeNull();
});

test('the manager stands alone rather than sitting in the sidebar shell', function () {
    $content = $this->get(route('admin.items'))->assertOk()->getContent();

    expect($content)->toContain('id="admin-content"')
        ->not->toContain('data-flux-sidebar')
        ->not->toContain('data-test="sidebar-menu-button"');
});

test('the standalone manager keeps a way out to the list and the account', function () {
    $this->get(route('admin.items'))
        ->assertOk()
        ->assertSee(route('home'))
        ->assertSee(route('profile.edit'))
        ->assertSee(route('logout'));
});

test('the manager carries the same light and dark switch as the list', function () {
    $content = $this->get(route('admin.items'))->assertOk()->getContent();

    expect($content)->toContain('data-test="appearance-toggle"')
        ->toContain("window.localStorage.getItem('flux.appearance') ?? 'dark'");
});
