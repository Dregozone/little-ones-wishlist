<?php

use App\Http\Middleware\EnsureGuestHasVerified;

test('the home page sends unverified visitors to the gate', function () {
    $this->get(route('home'))->assertRedirect(route('wishlist.verify'));
});

test('returns a successful response', function () {
    $this->withCookie(EnsureGuestHasVerified::COOKIE, '1')
        ->get(route('home'))
        ->assertOk();
});
