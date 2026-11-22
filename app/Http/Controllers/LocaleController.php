<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Http\Requests\LocaleUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    /**
     * Switch the UI language. Guests keep it in the session; signed-in users also get it saved.
     */
    public function update(LocaleUpdateRequest $request): RedirectResponse
    {
        $locale = $request->locale();

        $request->session()->put(SetLocale::SESSION_KEY, $locale->value);

        $user = $request->user();

        if ($user instanceof User) {
            $user->update(['locale' => $locale]);
        }

        return back();
    }
}
