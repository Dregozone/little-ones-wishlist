<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One device's handle on a guest's identity.
 *
 * Only the SHA-256 digest is stored. The plaintext lives in the guest's cookie and in the
 * recovery link they may have saved, so a leak of this table cannot be replayed.
 *
 * @property int $id
 * @property int $guest_id
 * @property string $token_hash
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Guest $guest
 */
#[Fillable(['guest_id', 'token_hash', 'last_used_at'])]
#[Hidden(['token_hash'])]
class GuestToken extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * The guest this token identifies.
     *
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
