<?php

use App\Actions\VerifyParentNames;
use App\Http\Middleware\EnsureGuestHasVerified;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

test('the list is hidden behind the gate', function () {
    $this->get(route('home'))->assertRedirect(route('wishlist.verify'));
});

test('the gate itself is reachable', function () {
    $this->get(route('wishlist.verify'))->assertOk();
});

test('a correct answer opens the list', function (string $answer) {
    Livewire::test('pages::wishlist.verify')
        ->set('answer', $answer)
        ->call('verify')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    expect(session(EnsureGuestHasVerified::SESSION_KEY))->toBeTrue();
})->with([
    'first name' => 'Emma',
    'other first name' => 'Anders',
    'surname alone' => 'Learmonth',
    'both names' => 'Emma and Anders',
    'both names, ampersand' => 'Anders & Emma',
    'full name' => 'Emma Learmonth',
    'both names with surname' => 'Emma and Anders Learmonth',
    'lowercase' => 'emma',
    'shouting' => 'ANDERS',
    'untrimmed' => '  Emma  ',
    'comma separated' => 'Emma, Anders',
    'typo in a first name' => 'Ema',
    'typo in the other first name' => 'Andres',
    'typo in the surname' => 'Learmonth ',
]);

test('a wrong answer is refused', function (string $answer) {
    Livewire::test('pages::wishlist.verify')
        ->set('answer', $answer)
        ->call('verify')
        ->assertHasErrors('answer')
        ->assertNoRedirect();

    expect(session(EnsureGuestHasVerified::SESSION_KEY))->toBeNull();
})->with([
    'a stranger' => 'John',
    'the baby' => 'Baby',
    'one right one wrong' => 'Emma and John',
    'only joining words' => 'and',
    'punctuation only' => '???',
    'a guess at the theme' => 'Wishlist',
]);

test('an empty answer is refused', function () {
    Livewire::test('pages::wishlist.verify')
        ->set('answer', '')
        ->call('verify')
        ->assertHasErrors('answer');
});

test('a verified cookie lets a returning guest straight in', function () {
    $this->withCookie(EnsureGuestHasVerified::COOKIE, '1')
        ->get(route('home'))
        ->assertOk();
});

test('the gate is rate limited', function () {
    $component = Livewire::test('pages::wishlist.verify')->set('answer', 'Nope');

    foreach (range(1, 10) as $ignored) {
        $component->call('verify');
    }

    $component->set('answer', 'Emma')
        ->call('verify')
        ->assertHasErrors('answer')
        ->assertNoRedirect();

    RateLimiter::clear('wishlist-gate:127.0.0.1');
});

test('the name check normalises accents and punctuation', function () {
    $verify = new VerifyParentNames;

    expect($verify('Émma'))->toBeTrue()
        ->and($verify("Emma & Anders'"))->toBeTrue()
        ->and($verify('emma-learmonth'))->toBeTrue()
        ->and($verify(null))->toBeFalse()
        ->and($verify('   '))->toBeFalse();
});
