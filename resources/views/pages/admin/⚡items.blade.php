<?php

use App\Models\WishlistItem;
use Flux\Flux;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts::app')]
#[Title('Manage the wishlist')]
class extends Component {
    #[Url(as: 'q', except: '')]
    public string $search = '';

    /**
     * The pinned quick-add row at the top of the table.
     *
     * @var array{name: string, shop_name: string, price: string}
     */
    public array $draft = [
        'name' => '',
        'shop_name' => '',
        'price' => '',
    ];

    /** The cell currently being edited, as "{id}.{field}". */
    public ?string $editing = null;

    /** The working value of the cell being edited. */
    public string $editingValue = '';

    /** The row whose long-field drawer is open. */
    public ?int $expanded = null;

    /**
     * The long fields for the expanded row.
     *
     * @var array{description: string, product_url: string, image_url: string}
     */
    public array $details = [
        'description' => '',
        'product_url' => '',
        'image_url' => '',
    ];

    /** The row that just saved, so the interface can flash it. */
    public ?int $justSaved = null;

    /** The row awaiting delete confirmation. */
    public ?int $deleting = null;

    /**
     * Every item, newest first, with claim counts eager loaded.
     *
     * @return Collection<int, WishlistItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return WishlistItem::query()
            ->matching($this->search)
            ->withCount('claim')
            ->latest('id')
            ->get();
    }

    /**
     * A running summary for the page heading.
     *
     * @return array{total: int, claimed: int, hidden: int}
     */
    #[Computed]
    public function summary(): array
    {
        $items = $this->items;

        return [
            'total' => $items->count(),
            'claimed' => $items->where('claim_count', '>', 0)->count(),
            'hidden' => $items->where('is_visible', false)->count(),
        ];
    }

    /**
     * Create an item from the quick-add row.
     */
    public function addItem(): void
    {
        $validated = $this->validate(
            $this->quickAddRules(),
            attributes: $this->wishlistItemAttributes('draft.'),
        );

        $item = WishlistItem::create([
            'name' => $validated['draft']['name'],
            'shop_name' => $validated['draft']['shop_name'],
            'price_pennies' => (int) round(((float) $validated['draft']['price']) * 100),
        ]);

        $this->draft = ['name' => '', 'shop_name' => '', 'price' => ''];

        unset($this->items, $this->summary);

        $this->justSaved = $item->id;

        Flux::toast(variant: 'success', text: __(':item added to the list.', ['item' => $item->name]));
    }

    /**
     * Open a cell for editing.
     */
    public function edit(int $itemId, string $field): void
    {
        $item = WishlistItem::find($itemId);

        if ($item === null || ! in_array($field, ['name', 'shop_name', 'price'], true)) {
            return;
        }

        $this->resetErrorBag();

        $this->editing = $itemId.'.'.$field;
        $this->editingValue = $field === 'price'
            ? number_format($item->price, 2, '.', '')
            : (string) $item->{$field};
    }

    /**
     * Abandon an in-progress cell edit without saving.
     */
    public function cancelEdit(): void
    {
        $this->editing = null;
        $this->editingValue = '';
        $this->resetErrorBag();
    }

    /**
     * Save the cell currently being edited.
     */
    public function saveCell(): void
    {
        if ($this->editing === null) {
            return;
        }

        [$itemId, $field] = explode('.', $this->editing, 2);

        $item = WishlistItem::find((int) $itemId);

        if ($item === null) {
            $this->cancelEdit();

            return;
        }

        $rules = $this->wishlistItemRules();

        $this->validate(
            ['editingValue' => $rules[$field]],
            attributes: ['editingValue' => $this->wishlistItemAttributes()[$field]],
        );

        $item->update($field === 'price'
            ? ['price_pennies' => (int) round(((float) $this->editingValue) * 100)]
            : [$field => $this->editingValue]);

        $this->justSaved = $item->id;

        $this->cancelEdit();

        unset($this->items, $this->summary);
    }

    /**
     * Open or close the long-field drawer beneath a row.
     */
    public function toggleDetails(int $itemId): void
    {
        if ($this->expanded === $itemId) {
            $this->expanded = null;

            return;
        }

        $item = WishlistItem::find($itemId);

        if ($item === null) {
            return;
        }

        $this->resetErrorBag();

        $this->expanded = $itemId;
        $this->details = [
            'description' => (string) $item->description,
            'product_url' => (string) $item->product_url,
            'image_url' => (string) $item->image_url,
        ];
    }

    /**
     * Save the long fields from the drawer.
     */
    public function saveDetails(): void
    {
        $item = $this->expanded === null ? null : WishlistItem::find($this->expanded);

        if ($item === null) {
            return;
        }

        $rules = $this->wishlistItemRules('details.');

        $this->validate([
            'details.description' => $rules['details.description'],
            'details.product_url' => $rules['details.product_url'],
            'details.image_url' => $rules['details.image_url'],
        ], attributes: $this->wishlistItemAttributes('details.'));

        $item->update([
            'description' => $this->details['description'] ?: null,
            'product_url' => $this->details['product_url'] ?: null,
            'image_url' => $this->details['image_url'] ?: null,
        ]);

        $this->justSaved = $item->id;
        $this->expanded = null;

        unset($this->items, $this->summary);

        Flux::toast(variant: 'success', text: __('Details saved.'));
    }

    /**
     * Show or hide an item from the public list.
     */
    public function toggleVisibility(int $itemId): void
    {
        $item = WishlistItem::find($itemId);

        if ($item === null) {
            return;
        }

        $item->update(['is_visible' => ! $item->is_visible]);

        $this->justSaved = $item->id;

        unset($this->items, $this->summary);
    }

    /**
     * Free an item that a guest had claimed, in case they told you in person instead.
     */
    public function releaseClaim(int $itemId): void
    {
        $item = WishlistItem::find($itemId);

        if ($item === null) {
            return;
        }

        $item->claim()->delete();

        $this->justSaved = $item->id;

        unset($this->items, $this->summary);

        Flux::toast(variant: 'success', text: __(':item is available again.', ['item' => $item->name]));
    }

    /**
     * Ask before removing an item for good.
     */
    public function confirmDelete(int $itemId): void
    {
        $this->deleting = $itemId;

        Flux::modal('confirm-delete')->show();
    }

    /**
     * Remove an item and any claim against it.
     */
    public function deleteItem(): void
    {
        $item = $this->deleting === null ? null : WishlistItem::find($this->deleting);

        $this->deleting = null;

        Flux::modal('confirm-delete')->close();

        if ($item === null) {
            return;
        }

        $name = $item->name;

        $item->delete();

        unset($this->items, $this->summary);

        Flux::toast(variant: 'success', text: __(':item removed.', ['item' => $name]));
    }

    /**
     * The subset of the item rules that the quick-add row collects.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    private function quickAddRules(): array
    {
        $rules = $this->wishlistItemRules('draft.');

        return [
            'draft.name' => $rules['draft.name'],
            'draft.shop_name' => $rules['draft.shop_name'],
            'draft.price' => $rules['draft.price'],
        ];
    }

    /**
     * What makes a valid wishlist item.
     *
     * Prices arrive from the form in pounds and are converted to pennies on the way in, so
     * the rule is about what a person types rather than what is stored. The prefix lets the
     * quick-add row and the inline editor share one definition.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    private function wishlistItemRules(string $prefix = ''): array
    {
        return [
            $prefix.'name' => ['required', 'string', 'max:120'],
            $prefix.'shop_name' => ['required', 'string', 'max:80'],
            $prefix.'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            $prefix.'description' => ['nullable', 'string', 'max:500'],
            $prefix.'product_url' => ['nullable', 'url:http,https', 'max:2048'],
            $prefix.'image_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    /**
     * Friendly field names for validation messages.
     *
     * @return array<string, string>
     */
    private function wishlistItemAttributes(string $prefix = ''): array
    {
        return [
            $prefix.'name' => __('item name'),
            $prefix.'shop_name' => __('shop name'),
            $prefix.'price' => __('price'),
            $prefix.'description' => __('description'),
            $prefix.'product_url' => __('product link'),
            $prefix.'image_url' => __('image link'),
        ];
    }
}; ?>

<div class="flex w-full flex-1 flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Wishlist items') }}</flux:heading>
            <flux:subheading>
                {{ __(':total items, :claimed spoken for, :hidden hidden', [
                    'total' => $this->summary['total'],
                    'claimed' => $this->summary['claimed'],
                    'hidden' => $this->summary['hidden'],
                ]) }}
            </flux:subheading>
        </div>

        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            icon="magnifying-glass"
            :placeholder="__('Search items')"
            class="w-full sm:w-64"
            data-test="admin-search"
        />
    </div>

    {{-- Quick add. Kept as a form so Enter submits from any of the three fields. --}}
    <form
        wire:submit="addItem"
        class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900"
    >
        <flux:heading size="sm" class="mb-3">{{ __('Add an item') }}</flux:heading>

        <div class="grid gap-3 sm:grid-cols-[1fr_1fr_8rem_auto] sm:items-start">
            <flux:input
                wire:model="draft.name"
                :label="__('Item')"
                :placeholder="__('Cot mobile')"
                class="min-h-11"
                data-test="draft-name"
            />

            <flux:input
                wire:model="draft.shop_name"
                :label="__('Shop')"
                :placeholder="__('JoJo Maman Bébé')"
                class="min-h-11"
                data-test="draft-shop"
            />

            <flux:input
                wire:model="draft.price"
                type="number"
                step="0.01"
                min="0"
                :label="__('Price (£)')"
                placeholder="24.00"
                class="min-h-11"
                data-test="draft-price"
            />

            <flux:button
                type="submit"
                variant="primary"
                icon="plus"
                class="min-h-11 sm:mt-6 sm:self-start"
                data-test="draft-submit"
            >
                {{ __('Add') }}
            </flux:button>
        </div>
    </form>

    @if ($this->items->isEmpty())
        <div class="rounded-xl border border-dashed border-neutral-300 p-10 text-center dark:border-neutral-700">
            <flux:heading size="lg">{{ __('Nothing here yet') }}</flux:heading>
            <flux:text class="mt-1">
                {{ $search === '' ? __('Add your first item above.') : __('No items match that search.') }}
            </flux:text>
        </div>
    @else
        {{-- The table collapses to stacked cards below sm; the same markup serves both,
             with the header row hidden and each cell labelled at narrow widths. --}}
        <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="w-full min-w-0 text-start text-sm sm:min-w-[46rem]">
                <caption class="sr-only">
                    {{ __('Wishlist items. Select a cell to edit it in place.') }}
                </caption>

                <thead class="max-sm:hidden">
                    <tr class="border-b border-neutral-200 text-xs uppercase tracking-wide text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                        <th scope="col" class="w-16 p-3 text-start">{{ __('Image') }}</th>
                        <th scope="col" class="p-3 text-start">{{ __('Item') }}</th>
                        <th scope="col" class="p-3 text-start">{{ __('Shop') }}</th>
                        <th scope="col" class="w-24 p-3 text-end">{{ __('Price') }}</th>
                        <th scope="col" class="w-24 p-3 text-start">{{ __('Status') }}</th>
                        <th scope="col" class="w-28 p-3 text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($this->items as $item)
                        <tr
                            wire:key="row-{{ $item->id }}"
                            @class([
                                'border-b border-neutral-200 align-middle dark:border-neutral-700 max-sm:grid max-sm:grid-cols-2 max-sm:gap-2 max-sm:p-3',
                                'wl-flash' => $justSaved === $item->id,
                                'opacity-60' => ! $item->is_visible,
                            ])
                        >
                            <td class="p-3 max-sm:col-span-2 max-sm:p-0">
                                <x-wishlist.remote-image
                                    :src="$item->image_url"
                                    alt=""
                                    class="!aspect-square size-12 max-sm:size-16"
                                />
                            </td>

                            <x-admin.editable-cell
                                :item="$item"
                                field="name"
                                :editing="$editing"
                                :label="__('Item')"
                            />

                            <x-admin.editable-cell
                                :item="$item"
                                field="shop_name"
                                :editing="$editing"
                                :label="__('Shop')"
                            />

                            <x-admin.editable-cell
                                :item="$item"
                                field="price"
                                :editing="$editing"
                                :label="__('Price')"
                                align="end"
                            />

                            <td class="p-3 max-sm:p-0">
                                <span class="text-xs font-medium text-neutral-500 sm:hidden dark:text-neutral-400">
                                    {{ __('Status') }}
                                </span>

                                {{-- Claim counts only. Which guest claimed an item is never
                                     surfaced here: guests were promised anonymity, and that
                                     promise has to hold in the admin as well. --}}
                                @if ($item->claim_count > 0)
                                    <flux:badge size="sm" color="blue" icon="lock-closed">
                                        {{ __('Claimed') }}
                                    </flux:badge>
                                @elseif (! $item->is_visible)
                                    <flux:badge size="sm" color="zinc" icon="eye-slash">
                                        {{ __('Hidden') }}
                                    </flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">{{ __('Available') }}</flux:badge>
                                @endif
                            </td>

                            <td class="p-3 text-end max-sm:p-0">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="ellipsis-vertical"
                                        class="min-h-11 min-w-11"
                                        :aria-label="__('Actions for :item', ['item' => $item->name])"
                                        data-test="actions-{{ $item->id }}"
                                    />

                                    <flux:menu>
                                        <flux:menu.item
                                            icon="pencil-square"
                                            wire:click="toggleDetails({{ $item->id }})"
                                            data-test="details-{{ $item->id }}"
                                        >
                                            {{ __('Link, image and description') }}
                                        </flux:menu.item>

                                        <flux:menu.item
                                            :icon="$item->is_visible ? 'eye-slash' : 'eye'"
                                            wire:click="toggleVisibility({{ $item->id }})"
                                            data-test="visibility-{{ $item->id }}"
                                        >
                                            {{ $item->is_visible ? __('Hide from guests') : __('Show to guests') }}
                                        </flux:menu.item>

                                        @if ($item->claim_count > 0)
                                            <flux:menu.item
                                                icon="arrow-uturn-left"
                                                wire:click="releaseClaim({{ $item->id }})"
                                                wire:confirm="{{ __('Free this item so somebody else can claim it?') }}"
                                                data-test="release-claim-{{ $item->id }}"
                                            >
                                                {{ __('Free up the claim') }}
                                            </flux:menu.item>
                                        @endif

                                        <flux:menu.separator />

                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            wire:click="confirmDelete({{ $item->id }})"
                                            data-test="delete-{{ $item->id }}"
                                        >
                                            {{ __('Delete') }}
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>

                        @if ($expanded === $item->id)
                            <tr wire:key="details-{{ $item->id }}" class="border-b border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900/60">
                                <td colspan="6" class="p-4 max-sm:block">
                                    <form wire:submit="saveDetails" class="grid gap-4 lg:grid-cols-[1fr_16rem]">
                                        <div class="space-y-3">
                                            <flux:input
                                                wire:model="details.product_url"
                                                :label="__('Product link')"
                                                type="url"
                                                placeholder="https://"
                                                class="min-h-11"
                                                data-test="details-url"
                                            />

                                            <flux:input
                                                wire:model.blur="details.image_url"
                                                :label="__('Image link')"
                                                type="url"
                                                placeholder="https://"
                                                class="min-h-11"
                                                data-test="details-image"
                                            />

                                            <flux:textarea
                                                wire:model="details.description"
                                                :label="__('Description')"
                                                rows="3"
                                                data-test="details-description"
                                            />

                                            <div class="flex gap-2">
                                                <flux:button type="submit" variant="primary" class="min-h-11" data-test="details-save">
                                                    {{ __('Save') }}
                                                </flux:button>

                                                <flux:button
                                                    type="button"
                                                    variant="ghost"
                                                    class="min-h-11"
                                                    wire:click="toggleDetails({{ $item->id }})"
                                                >
                                                    {{ __('Close') }}
                                                </flux:button>
                                            </div>
                                        </div>

                                        <div>
                                            <flux:label>{{ __('How guests will see it') }}</flux:label>

                                            <div class="mt-2 max-w-64">
                                                <x-wishlist.remote-image
                                                    :src="$details['image_url']"
                                                    :alt="$item->name"
                                                />
                                                <p class="mt-2 text-xs uppercase tracking-widest text-neutral-500">{{ $item->shop_name }}</p>
                                                <p class="font-medium">{{ $item->name }}</p>
                                                <p class="text-sm text-neutral-500">{{ $item->formatted_price }}</p>
                                                <p class="mt-1 line-clamp-3 text-sm text-neutral-500">{{ $details['description'] }}</p>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <flux:modal name="confirm-delete" class="max-w-sm">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Delete this item?') }}</flux:heading>
            <flux:text>{{ __('It disappears from the list for everyone, and any claim against it goes with it. This cannot be undone.') }}</flux:text>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost" class="min-h-11 w-full sm:w-auto">{{ __('Keep it') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" class="min-h-11 w-full sm:w-auto" wire:click="deleteItem" data-test="confirm-delete">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
