<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreateVerifiedUser;
use App\Http\Controllers\Controller;
use App\Support\Auth\SignInOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        abort_unless(SignInOptions::google(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(FindOrCreateVerifiedUser $users): RedirectResponse
    {
        abort_unless(SignInOptions::google(), 404);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            // Cancelled consent screen, expired state, network error...
            report($exception);

            return $this->failed('messages.google.failed');
        }

        $email = $googleUser->getEmail();

        // Only trust the address (and link it to an existing account) when Google says it is verified.
        if (! $googleUser instanceof GoogleUser || $email === null || ($googleUser->getRaw()['email_verified'] ?? false) !== true) {
            return $this->failed('messages.google.unverified');
        }

        $user = $users->handle($email, $googleUser->getName(), (string) $googleUser->getId());

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function failed(string $messageKey): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => __($messageKey)]);

        return to_route('login');
    }
}
