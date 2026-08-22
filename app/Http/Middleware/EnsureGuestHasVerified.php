<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the wishlist behind the "who is this baby list for?" question.
 */
class EnsureGuestHasVerified
{
    /**
     * The cookie recording that this browser has already answered the question.
     */
    public const COOKIE = 'wl_verified';

    /**
     * The session key recording the same thing for the current session.
     */
    public const SESSION_KEY = 'wl_verified';

    /**
     * How long a correct answer is remembered for, in minutes.
     */
    public const LIFETIME = 60 * 24 * 365;

    /**
     * Send unverified visitors to the gate.
     *
     * The cookie is checked as well as the session so that guests answer once, rather than
     * once per session, which for an occasional visit would be almost every time.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get(self::SESSION_KEY) === true) {
            return $next($request);
        }

        if ($request->cookie(self::COOKIE)) {
            $request->session()->put(self::SESSION_KEY, true);

            return $next($request);
        }

        return redirect()->route('wishlist.verify');
    }
}
