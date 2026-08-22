<?php

namespace Database\Factories;

use App\Models\Guest;
use App\Models\WishlistClaim;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'shop_name' => fake()->company(),
            'description' => fake()->sentence(),
            'price_pennies' => fake()->numberBetween(300, 15000),
            'product_url' => fake()->url(),
            'image_url' => fake()->imageUrl(640, 480),
            'cached_image_path' => null,
            'is_visible' => true,
        ];
    }

    /**
     * An item the parents have drafted but not yet published to the list.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_visible' => false,
        ]);
    }

    /**
     * An item somebody has already committed to buying.
     */
    public function claimed(?Guest $guest = null): static
    {
        return $this->afterCreating(function (WishlistItem $item) use ($guest): void {
            WishlistClaim::create([
                'wishlist_item_id' => $item->id,
                'guest_id' => ($guest ?? Guest::factory()->create())->id,
            ]);
        });
    }
}
