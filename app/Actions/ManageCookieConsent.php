<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Records whether a guest has agreed to the identity cookie.
 *
 * The cookie remembering that they passed the name question is strictly necessary and needs
 * only disclosure. The one that remembers which device claimed which items is a convenience,
 * so it is only ever set once the guest has said yes.
 */
class ManageCookieConsent
{
    /**
     * The cookie recording the guest's decision about the identity cookie.
     */
    public const COOKIE = 'wl_consent';

    /**
     * How long the decision is remembered for, in minutes.
     */
    public const LIFETIME = 60 * 24 * 365;

    /**
     * The session key mirroring the decision for the current session.
     */
    private const SESSION_KEY = 'wl_consent';

    /**
     * Whether the guest has made a decision either way.
     */
    public function hasDecided(Request $request): bool
    {
        return $this->decision($request) !== null;
    }

    /**
     * Whether the guest has agreed to the identity cookie.
     */
    public function hasAccepted(Request $request): bool
    {
        return $this->decision($request) === true;
    }

    /**
     * Record the guest's decision.
     */
    public function store(Request $request, bool $accepted): void
    {
        session()->put(self::SESSION_KEY, $accepted);

        Cookie::queue(Cookie::make(
            name: self::COOKIE,
            value: $accepted ? 'yes' : 'no',
            minutes: self::LIFETIME,
            httpOnly: true,
            sameSite: 'lax',
        ));
    }

    /**
     * The guest's decision, or null if they have not answered yet.
     */
    private function decision(Request $request): ?bool
    {
        $session = session()->get(self::SESSION_KEY);

        if (is_bool($session)) {
            return $session;
        }

        $cookie = $request->cookie(self::COOKIE);

        return match ($cookie) {
            'yes' => true,
            'no' => false,
            default => null,
        };
    }
}
