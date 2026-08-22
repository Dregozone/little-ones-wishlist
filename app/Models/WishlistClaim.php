<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wishlist_item_id
 * @property int $guest_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read WishlistItem $item
 * @property-read Guest $guest
 */
#[Fillable(['wishlist_item_id', 'guest_id'])]
class WishlistClaim extends Model
{
    /**
     * The item this claim is against.
     *
     * @return BelongsTo<WishlistItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(WishlistItem::class, 'wishlist_item_id');
    }

    /**
     * The anonymous guest who made this claim.
     *
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
