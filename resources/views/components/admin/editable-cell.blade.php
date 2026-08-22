@props([
    'item',
    'field',
    'label',
    'editing' => null,
    'align' => 'start',
])

@php
    $key = $item->id.'.'.$field;
    $isEditing = $editing === $key;
    $value = $field === 'price' ? $item->formatted_price : $item->{$field};
@endphp

<td class="p-3 text-{{ $align }} max-sm:flex max-sm:items-baseline max-sm:justify-between max-sm:gap-3 max-sm:p-0 max-sm:text-start">
    <span class="text-xs font-medium text-neutral-500 sm:hidden dark:text-neutral-400">{{ $label }}</span>

    @if ($isEditing)
        {{--
            Escape reverts and Enter commits, which is what anyone who has used a spreadsheet
            expects. Blur also commits, so tabbing straight on to the next cell keeps the edit.
        --}}
        <flux:input
            wire:model="editingValue"
            wire:keydown.enter.prevent="saveCell"
            wire:keydown.escape.prevent="cancelEdit"
            wire:blur="saveCell"
            :type="$field === 'price' ? 'number' : 'text'"
            :step="$field === 'price' ? '0.01' : null"
            :min="$field === 'price' ? '0' : null"
            size="sm"
            autofocus
            class="min-h-11 w-full"
            :aria-label="__('Edit :field for :item', ['field' => $label, 'item' => $item->name])"
            data-test="editing-{{ $key }}"
        />

        <flux:error name="editingValue" />
    @else
        <button
            type="button"
            wire:click="edit({{ $item->id }}, '{{ $field }}')"
            class="-mx-2 min-h-11 w-full rounded-md px-2 text-{{ $align }} hover:bg-neutral-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent max-sm:w-auto max-sm:text-end dark:hover:bg-neutral-800"
            data-test="cell-{{ $key }}"
        >
            <span class="sr-only">{{ __('Edit :field:', ['field' => $label]) }}</span>
            <span @class(['tabular-nums' => $field === 'price', 'font-medium' => $field === 'name'])>
                {{ $value }}
            </span>
        </button>
    @endif
</td>
