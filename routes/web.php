<?php

use App\Http\Controllers\RestoreGuestController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::wishlist.index')
    ->middleware('verified.guest')
    ->name('home');

Route::livewire('verify', 'pages::wishlist.verify')->name('wishlist.verify');

// Outside the name gate on purpose: a guest following their own saved link gets their
// identity back first, so answering the question afterwards does not cost them their picks.
Route::get('r/{token}', RestoreGuestController::class)
    ->where('token', '[A-Za-z0-9]{16,128}')
    ->name('wishlist.restore');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('admin/items', 'pages::admin.items')->name('admin.items');
});

require __DIR__.'/settings.php';
