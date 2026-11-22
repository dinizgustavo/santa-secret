<?php

use App\Http\Controllers\DrawController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMembershipController;
use App\Http\Controllers\GroupRulesController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', fn (): Response => Inertia::render('welcome'))->name('home');

Route::put('locale', [LocaleController::class, 'update'])
    ->middleware('throttle:30,1')
    ->name('locale.update');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [GroupController::class, 'index'])->name('dashboard');

    Route::get('groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('groups', [GroupController::class, 'store'])->middleware('throttle:20,1')->name('groups.store');
    Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('groups/{group}/edit', [GroupController::class, 'edit'])->name('groups.edit');
    Route::put('groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

    Route::post('groups/{group}/invite', [GroupMembershipController::class, 'regenerateInvite'])->name('groups.invite.regenerate');
    Route::delete('groups/{group}/membership', [GroupMembershipController::class, 'leave'])->name('groups.leave');

    Route::scopeBindings()->group(function () {
        Route::delete('groups/{group}/participants/{participant}', [GroupMembershipController::class, 'remove'])->name('groups.participants.destroy');
        Route::put('groups/{group}/participants/{participant}/gift-rule', [GroupRulesController::class, 'updateGiftRule'])->name('groups.participants.gift-rule');
        Route::delete('groups/{group}/exclusions/{exclusion}', [GroupRulesController::class, 'destroyExclusion'])->name('groups.exclusions.destroy');
    });

    Route::post('groups/{group}/exclusions', [GroupRulesController::class, 'storeExclusion'])->name('groups.exclusions.store');

    Route::post('groups/{group}/draw', [DrawController::class, 'store'])->middleware('throttle:draw')->name('groups.draw.store');
    Route::delete('groups/{group}/draw', [DrawController::class, 'destroy'])->middleware('throttle:draw')->name('groups.draw.destroy');

    // Guests opening an invite are sent to log in and brought back here afterwards.
    Route::get('invites/{token}', [InvitationController::class, 'show'])->name('invites.show');
    Route::post('invites/{token}', [InvitationController::class, 'store'])->middleware('throttle:join')->name('invites.accept');
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
