<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it creates an admin', function () {
    $this->artisan('make:admin', [
        'email' => 'anders@example.com',
        'password' => 'a-long-enough-password',
    ])->assertSuccessful();

    $user = User::sole();

    expect($user->email)->toBe('anders@example.com')
        ->and($user->name)->toBe('Anders')
        ->and(Hash::check('a-long-enough-password', $user->password))->toBeTrue();
});

test('it takes a display name when given one', function () {
    $this->artisan('make:admin', [
        'email' => 'hello@example.com',
        'password' => 'a-long-enough-password',
        '--name' => 'Emma Learmonth',
    ])->assertSuccessful();

    expect(User::sole()->name)->toBe('Emma Learmonth');
});

test('it derives a tidy name from the email address', function (string $email, string $expected) {
    $this->artisan('make:admin', [
        'email' => $email,
        'password' => 'a-long-enough-password',
    ])->assertSuccessful();

    expect(User::sole()->name)->toBe($expected);
})->with([
    ['emma.learmonth@example.com', 'Emma Learmonth'],
    ['anders_learmonth@example.com', 'Anders Learmonth'],
    ['emma-anders@example.com', 'Emma Anders'],
]);

test('it refuses an email that already has an account', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('make:admin', [
        'email' => 'taken@example.com',
        'password' => 'a-long-enough-password',
    ])->assertFailed();

    expect(User::count())->toBe(1);
});

test('it resets an existing password when forced', function () {
    $user = User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('make:admin', [
        'email' => 'taken@example.com',
        'password' => 'a-brand-new-password',
        '--force' => true,
    ])->assertSuccessful();

    expect(User::count())->toBe(1)
        ->and(Hash::check('a-brand-new-password', $user->refresh()->password))->toBeTrue();
});

test('it refuses an address that is not an email', function () {
    $this->artisan('make:admin', [
        'email' => 'not-an-email',
        'password' => 'a-long-enough-password',
    ])->assertFailed();

    expect(User::count())->toBe(0);
});

test('it refuses an empty password', function () {
    $this->artisan('make:admin', [
        'email' => 'anders@example.com',
        'password' => '',
    ])->assertFailed();

    expect(User::count())->toBe(0);
});

test('it applies the application password policy', function () {
    // Password::defaults() only tightens up outside local, which is exactly the
    // environment an admin would be created in for real.
    app()['env'] = 'production';

    $this->artisan('make:admin', [
        'email' => 'anders@example.com',
        'password' => 'short',
    ])->assertFailed();

    expect(User::count())->toBe(0);
});
