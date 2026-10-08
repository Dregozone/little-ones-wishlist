<?php

use App\Http\Middleware\EnsureGuestHasVerified;

test('pages are titled with the project name instead of laravel', function () {
    $this->withCookie(EnsureGuestHasVerified::COOKIE, '1')
        ->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['<title>', e("Little one's wish list"), '</title>'], false)
        ->assertDontSee('- Laravel', false);
});

test('pages link the gift favicon', function () {
    $this->withCookie(EnsureGuestHasVerified::COOKIE, '1')
        ->get(route('home'))
        ->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', false);

    expect(file_get_contents(public_path('favicon.svg')))->toContain('#FB7185');
});
