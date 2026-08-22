<?php

namespace App\Actions;

use App\Models\Guest;
use App\Models\GuestToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Works out which anonymous guest is making the current request.
 *
 * Identity is layered, because guests never log in and we would rather not lose somebody's
 * commitments:
 *
 *  1. A server-set, encrypted, HttpOnly cookie. This is the durable one — Safari's tracking
 *     prevention caps script-written cookies and localStorage at seven days, but leaves
 *     server-set cookies alone.
 *  2. A localStorage mirror, replayed through the recovery route when the cookie has gone.
 *  3. A hashed IP address, which is only ever used to *offer* a match behind a confirmation
 *     prompt. Silently adopting an IP match would hand one guest another guest's claims on
 *     shared household wifi or carrier-grade NAT.
 *
 * Recognising a guest always mints an additional token rather than replacing the existing
 * one, so picking the list up on a second device never locks the first one out.
 */
class ResolveGuest
{
    /**
     * The cookie holding this device's plaintext identity token.
     */
    public const COOKIE = 'wl_guest';

    /**
     * How long the identity cookie survives, in minutes.
     */
    public const LIFETIME = 60 * 24 * 365;

    /**
     * The session key used for guests who declined the cookie.
     */
    public const SESSION_KEY = 'wl_guest_token';

    /**
     * Resolve the guest behind this request, without creating one.
     */
    public function __invoke(Request $request): ?Guest
    {
        $token = $this->token($request);

        if ($token === null) {
            return null;
        }

        return $this->guestForToken($token, $request);
    }

    /**
     * Resolve the guest behind this request, creating and remembering one if needed.
     */
    public function orCreate(Request $request, bool $hasConsented): Guest
    {
        if ($guest = $this($request)) {
            if ($hasConsented && ! $guest->has_consented) {
                $guest->forceFill(['has_consented' => true])->save();
                $this->issueToken($request, $guest);
            }

            return $guest;
        }

        $guest = Guest::create([
            'ip_hash' => Guest::hashIp((string) $request->ip()),
            'has_consented' => $hasConsented,
            'last_seen_at' => now(),
        ]);

        $this->issueToken($request, $guest);

        return $guest;
    }

    /**
     * Find the guest a plaintext token belongs to, refreshing when we last saw them.
     */
    public function guestForToken(string $token, ?Request $request = null): ?Guest
    {
        $record = GuestToken::query()
            ->with('guest')
            ->firstWhere('token_hash', Guest::hashToken($token));

        if ($record === null) {
            return null;
        }

        $record->forceFill(['last_used_at' => now()])->save();

        $guest = $record->guest;

        $guest->forceFill([
            'last_seen_at' => now(),
            'ip_hash' => $request ? Guest::hashIp((string) $request->ip()) : $guest->ip_hash,
        ])->save();

        return $guest;
    }

    /**
     * Give this device its own token for the guest and remember it.
     */
    public function issueToken(Request $request, Guest $guest): string
    {
        $token = $guest->issueToken();

        // The session store is taken from the container rather than the request, because
        // a Livewire request carries the session on the container binding, not on the
        // Request instance handed to the component.
        session()->put(self::SESSION_KEY, $token);

        if ($guest->has_consented) {
            Cookie::queue(Cookie::make(
                name: self::COOKIE,
                value: $token,
                minutes: self::LIFETIME,
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        return $token;
    }

    /**
     * The plaintext identity token for this request, if we have one at all.
     */
    public function token(Request $request): ?string
    {
        $cookie = $request->cookie(self::COOKIE);

        if (is_string($cookie) && $cookie !== '') {
            return $cookie;
        }

        $session = session()->get(self::SESSION_KEY);

        return is_string($session) && $session !== '' ? $session : null;
    }

    /**
     * A guest previously seen from this IP address, offered to the visitor for confirmation
     * rather than applied automatically.
     */
    public function candidateFromIp(Request $request): ?Guest
    {
        return Guest::query()
            ->where('ip_hash', Guest::hashIp((string) $request->ip()))
            ->where('has_consented', true)
            ->has('claims')
            ->latest('last_seen_at')
            ->first();
    }
}
