<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\WishlistClaim;
use App\Models\WishlistItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WishlistItemSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * A realistic sample list so the layout can be judged with real-world content lengths.
     *
     * @var list<array{name: string, shop_name: string, price_pennies: int, description: string}>
     */
    private const array ITEMS = [
        ['name' => 'Cot Mobile', 'shop_name' => 'JoJo Maman Bébé', 'price_pennies' => 2400, 'description' => 'Soft grey felt stars and clouds, plays a gentle lullaby.'],
        ['name' => 'Foam Play Mat', 'shop_name' => 'IKEA', 'price_pennies' => 900, 'description' => 'Interlocking foam tiles in oatmeal and sage.'],
        ['name' => 'Baby Bath Kit', 'shop_name' => 'Boots', 'price_pennies' => 1800, 'description' => 'Bath support, hooded towel and fragrance-free wash.'],
        ['name' => 'Muslin Squares (Pack of 12)', 'shop_name' => 'Aden + Anais', 'price_pennies' => 2800, 'description' => 'Bamboo muslins, endlessly useful and you can never have too many.'],
        ['name' => 'Bouncer Chair', 'shop_name' => 'BabyBjörn', 'price_pennies' => 15900, 'description' => 'Ergonomic bouncer in natural mesh, folds flat for storage.'],
        ['name' => 'Blackout Blind', 'shop_name' => 'Gro Company', 'price_pennies' => 3200, 'description' => 'Portable blackout blind that sticks to any window.'],
        ['name' => 'Nursing Pillow', 'shop_name' => 'Widgey', 'price_pennies' => 3500, 'description' => 'Doubles as a support cushion for tummy time later on.'],
        ['name' => 'Sleepsuits (0-3m, Pack of 5)', 'shop_name' => 'M&S', 'price_pennies' => 1600, 'description' => 'Neutral cream and sage cotton sleepsuits with fold-over mitts.'],
        ['name' => 'Changing Bag', 'shop_name' => 'Storksak', 'price_pennies' => 8500, 'description' => 'Wipe-clean backpack with insulated bottle pockets and a changing mat.'],
        ['name' => 'White Noise Machine', 'shop_name' => 'Yogasleep', 'price_pennies' => 4000, 'description' => 'Genuine fan-based white noise rather than a looping recording.'],
        ['name' => 'Wooden Stacking Rings', 'shop_name' => 'Hape', 'price_pennies' => 1400, 'description' => 'Classic first toy in non-toxic painted beech.'],
        ['name' => 'Bibs (Pack of 10)', 'shop_name' => 'H&M', 'price_pennies' => 600, 'description' => 'Cotton dribble bibs with poppers, plain and striped.'],
        ['name' => 'Baby Monitor', 'shop_name' => 'Motorola', 'price_pennies' => 11000, 'description' => 'Audio and video monitor with a room temperature readout.'],
        ['name' => 'Swaddle Blankets', 'shop_name' => 'Love To Dream', 'price_pennies' => 2900, 'description' => 'Arms-up swaddle in two sizes, the newborn shape that actually works.'],
        ['name' => 'Nappy Caddy', 'shop_name' => 'Amazon', 'price_pennies' => 2200, 'description' => 'Felt organiser to keep nappies, wipes and creams in one carryable place.'],
        ['name' => 'Pram Footmuff', 'shop_name' => 'Silver Cross', 'price_pennies' => 6000, 'description' => 'Fleece-lined footmuff in stone, universal pram fitting.'],
        ['name' => 'Bottle Steriliser', 'shop_name' => 'Tommee Tippee', 'price_pennies' => 4500, 'description' => 'Electric steam steriliser, holds six bottles.'],
        ['name' => 'Board Book Bundle', 'shop_name' => 'Waterstones', 'price_pennies' => 2000, 'description' => 'Five chunky board books for the very first bookshelf.'],
        ['name' => 'Room Thermometer', 'shop_name' => 'Gro Company', 'price_pennies' => 1500, 'description' => 'Egg-shaped thermometer that glows to show if the room is too warm.'],
        ['name' => 'Highchair', 'shop_name' => 'Stokke', 'price_pennies' => 19900, 'description' => 'Adjustable wooden highchair that grows with the child. A big one, perhaps to share.'],
    ];

    /**
     * Seed the wishlist with sample items, a few of which are already spoken for.
     */
    public function run(): void
    {
        $items = collect(self::ITEMS)->map(fn (array $item): WishlistItem => WishlistItem::create([
            ...$item,
            'product_url' => 'https://example.com/'.Str::slug($item['name']),
            'image_url' => 'https://picsum.photos/seed/'.Str::slug($item['name']).'/640/480',
        ]));

        $guests = Guest::factory()->count(3)->create();

        $items->take(6)->each(fn (WishlistItem $item, int $index) => WishlistClaim::create([
            'wishlist_item_id' => $item->id,
            'guest_id' => $guests[$index % $guests->count()]->id,
        ]));
    }
}
