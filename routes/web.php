<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', fn (): Response => Inertia::render('welcome'))->name('home');

Route::put('locale', [LocaleController::class, 'update'])
    ->middleware('throttle:30,1')
    ->name('locale.update');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', fn (): Response => Inertia::render('dashboard'))->name('dashboard');
});

require __DIR__.'/settings.php';
