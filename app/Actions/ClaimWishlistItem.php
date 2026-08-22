<?php

namespace App\Actions;

use App\Models\Guest;
use App\Models\WishlistClaim;
use App\Models\WishlistItem;
use Illuminate\Database\QueryException;

/**
 * Commits a guest to buying an item.
 */
class ClaimWishlistItem
{
    /**
     * Claim the item for the guest, returning false if somebody else got there first.
     *
     * The unique index on wishlist_claims.wishlist_item_id is what actually guarantees a
     * single claimant: two guests tapping at the same moment will race, and the loser is
     * caught here rather than quietly overwriting the winner.
     */
    public function __invoke(WishlistItem $item, Guest $guest): bool
    {
        if (! $item->is_visible) {
            return false;
        }

        try {
            WishlistClaim::create([
                'wishlist_item_id' => $item->id,
                'guest_id' => $guest->id,
            ]);
        } catch (QueryException) {
            return false;
        }

        return true;
    }
}
