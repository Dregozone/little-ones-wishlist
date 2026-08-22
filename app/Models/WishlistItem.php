<?php

namespace App\Models;

use Database\Factories\WishlistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

/**
 * @property int $id
 * @property string $name
 * @property string $shop_name
 * @property string|null $description
 * @property int $price_pennies
 * @property string|null $product_url
 * @property string|null $image_url
 * @property string|null $cached_image_path
 * @property bool $is_visible
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read float $price
 * @property-read string $formatted_price
 * @property-read WishlistClaim|null $claim
 */
#[Fillable([
    'name',
    'shop_name',
    'description',
    'price_pennies',
    'product_url',
    'image_url',
    'is_visible',
])]
class WishlistItem extends Model
{
    /** @use HasFactory<WishlistItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_pennies' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * The single claim held against this item, if anybody has committed to buying it.
     *
     * @return HasOne<WishlistClaim, $this>
     */
    public function claim(): HasOne
    {
        return $this->hasOne(WishlistClaim::class);
    }

    /**
     * The price in pounds, derived from the pennies stored on the record.
     *
     * @return Attribute<float, never>
     */
    protected function price(): Attribute
    {
        return Attribute::get(fn (): float => $this->price_pennies / 100);
    }

    /**
     * The price rendered as sterling for display.
     *
     * @return Attribute<string, never>
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn (): string => (string) Number::currency($this->price, 'GBP', 'en_GB'));
    }

    /**
     * Limit the query to items that guests are allowed to see.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * Limit the query to items matching a free-text term across name, shop and description.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeMatching(Builder $query, string $term): void
    {
        // LIKE wildcards are neutralised rather than escaped: escaping would need an
        // ESCAPE clause that differs between SQLite and MySQL, and nobody searching a baby
        // wishlist means "%" literally.
        $term = trim(str_replace(['%', '_'], ' ', $term));

        if ($term === '') {
            return;
        }

        $pattern = '%'.$term.'%';

        $query->where(function (Builder $query) use ($pattern): void {
            $query->where('name', 'like', $pattern)
                ->orWhere('shop_name', 'like', $pattern)
                ->orWhere('description', 'like', $pattern);
        });
    }
}
