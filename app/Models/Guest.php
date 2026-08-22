<?php

namespace App\Models;

use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An anonymous visitor who has claimed at least one item.
 *
 * There is deliberately no name, email or raw IP address here. The parents must never be
 * able to learn who claimed what, so the only thing linking a guest to a person is a token
 * held on that person's own device, and the only trace of their network is a salted digest.
 *
 * @property int $id
 * @property string|null $ip_hash
 * @property bool $has_consented
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, WishlistClaim> $claims
 * @property-read Collection<int, GuestToken> $tokens
 */
#[Fillable(['ip_hash', 'has_consented', 'last_seen_at'])]
#[Hidden(['ip_hash'])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_consented' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * The items this guest has committed to buying.
     *
     * @return HasMany<WishlistClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(WishlistClaim::class);
    }

    /**
     * The device tokens that can be used to recognise this guest.
     *
     * @return HasMany<GuestToken, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(GuestToken::class);
    }

    /**
     * Mint a new device token for this guest, returning the plaintext to hand to the browser.
     */
    public function issueToken(): string
    {
        $token = Str::random(64);

        $this->tokens()->create([
            'token_hash' => self::hashToken($token),
            'last_used_at' => now(),
        ]);

        return $token;
    }

    /**
     * Hash a plaintext identity token for storage or lookup.
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Hash an IP address, salted with the application key so the digest is useless elsewhere.
     */
    public static function hashIp(string $ip): string
    {
        return hash('sha256', $ip.'|'.config('app.key'));
    }
}
