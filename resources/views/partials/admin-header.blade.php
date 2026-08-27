{{--
    Sticky header for the manager.

    Same shape as the public list's header: the title block folds away once the page
    scrolls so the search field and the running counts stay in reach while a long table
    goes by. The account menu lives here because this page no longer has a sidebar.
--}}
{{-- The wrapper carries the Alpine scope so that the sentinel, which must sit in normal
     flow above the sticky element, shares $refs with the header that observes it. --}}
<div
    x-data="{ condensed: false }"
    x-init="
        new IntersectionObserver(
            ([entry]) => (condensed = !entry.isIntersecting),
            { threshold: 0 },
        ).observe($refs.sentinel)
    "
>
    <div x-ref="sentinel" class="h-px" aria-hidden="true"></div>

    <header class="sticky top-0 z-30 -mx-4 px-4 pt-4 sm:-mx-6 sm:px-6">
        <div
            class="wl-transition rounded-2xl border border-oat-200 bg-oat-50/85 p-4 shadow-sm shadow-oat-900/5 backdrop-blur-md sm:p-6 dark:border-oat-800 dark:bg-oat-900/85 dark:shadow-black/30"
            :class="condensed ? 'sm:p-4' : ''"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="wl-transition overflow-hidden" :class="condensed ? 'max-h-0 opacity-0' : 'max-h-40 opacity-100'">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-sage-700 dark:text-sage-300">
                        {{ __('Baby Learmonth') }}
                    </p>

                    <flux:heading size="xl" level="1" class="mt-1 text-oat-900 dark:text-oat-50">
                        {{ __('Manage the wishlist') }}
                    </flux:heading>

                    <flux:text class="mt-1 max-w-prose text-oat-700 dark:text-oat-300">
                        {{ __('Add items, edit them in place, and hide anything you would rather guests did not see yet.') }}
                    </flux:text>
                </div>

                <div class="ms-auto flex shrink-0 items-center gap-2">
                    <x-appearance-toggle />

                    <flux:button
                        :href="route('home')"
                        icon="arrow-top-right-on-square"
                        size="sm"
                        variant="ghost"
                        target="_blank"
                        class="min-h-11 max-sm:hidden"
                        data-test="view-public-list"
                    >
                        {{ __('View the list') }}
                    </flux:button>

                    <flux:dropdown position="bottom" align="end">
                        <flux:profile
                            :initials="auth()->user()->initials()"
                            icon-trailing="chevron-down"
                            data-test="account-menu-button"
                        />

                        <flux:menu>
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>

                            <flux:menu.separator />

                            <flux:menu.item :href="route('home')" icon="arrow-top-right-on-square" target="_blank" class="sm:hidden">
                                {{ __('View the list') }}
                            </flux:menu.item>

                            <flux:menu.item :href="route('dashboard')" icon="home" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:menu.item>

                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>

                            <flux:menu.separator />

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item
                                    as="button"
                                    type="submit"
                                    icon="arrow-right-start-on-rectangle"
                                    class="w-full cursor-pointer"
                                    data-test="logout-button"
                                >
                                    {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    icon="magnifying-glass"
                    :label="__('Search')"
                    :placeholder="__('Try a shop, an item, or a word')"
                    class="min-h-11"
                    data-test="admin-search"
                />

                {{-- Counts only: how many are spoken for, never who by. --}}
                <dl class="flex items-center gap-4 text-sm sm:pb-2">
                    @foreach ([
                        ['label' => __('Items'), 'value' => $this->summary['total']],
                        ['label' => __('Claimed'), 'value' => $this->summary['claimed']],
                        ['label' => __('Hidden'), 'value' => $this->summary['hidden']],
                    ] as $stat)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-oat-600 dark:text-oat-400">{{ $stat['label'] }}</dt>
                            <dd class="font-semibold tabular-nums text-oat-900 dark:text-oat-100">{{ $stat['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </header>
</div>
