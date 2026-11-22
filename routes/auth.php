<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginCodeController;
use Illuminate\Support\Facades\Route;

/*
| Sign-in methods on top of Fortify's e-mail + password: Google and e-mailed one-time codes.
| Each route returns 404 while its method is not configured (see App\Support\Auth\SignInOptions).
*/

Route::middleware(['guest', 'throttle:oauth'])->group(function () {
    Route::get('auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('guest')->group(function () {
    Route::get('login/code', [LoginCodeController::class, 'create'])->name('login-code.create');
    Route::post('login/code', [LoginCodeController::class, 'store'])
        ->middleware('throttle:login-code-send')
        ->name('login-code.store');

    Route::get('login/code/verify', [LoginCodeController::class, 'edit'])->name('login-code.edit');
    Route::post('login/code/verify', [LoginCodeController::class, 'verify'])
        ->middleware('throttle:login-code-verify')
        ->name('login-code.verify');
});
