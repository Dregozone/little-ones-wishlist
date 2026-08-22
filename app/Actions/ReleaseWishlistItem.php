<?php

namespace App\Actions;

use App\Models\Guest;
use App\Models\WishlistItem;

/**
 * Releases a guest's commitment so somebody else can pick the item up.
 */
class ReleaseWishlistItem
{
    /**
     * Release the item, returning false unless this guest is the one who claimed it.
     *
     * Ownership is re-checked here rather than trusted from the interface, so a crafted
     * request cannot release a claim belonging to somebody else.
     */
    public function __invoke(WishlistItem $item, Guest $guest): bool
    {
        return $item->claim()
            ->where('guest_id', $guest->id)
            ->delete() > 0;
    }
}
