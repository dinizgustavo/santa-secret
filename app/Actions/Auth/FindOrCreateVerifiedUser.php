<?php

namespace App\Actions\Auth;

use App\Enums\Locale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Resolves the account for an e-mail address whose ownership was just proven
 * (by Google or by an e-mailed code), creating it on first sign-in.
 */
final class FindOrCreateVerifiedUser
{
    public function handle(string $email, ?string $name = null, ?string $googleId = null): User
    {
        $email = Str::lower(trim($email));

        $user = ($googleId !== null ? User::query()->where('google_id', $googleId)->first() : null)
            ?? User::query()->where('email', $email)->first();

        if ($user === null) {
            return User::query()->forceCreate([
                'name' => $this->displayName($name, $email),
                'email' => $email,
                'email_verified_at' => now(),
                'google_id' => $googleId,
                'locale' => Locale::tryFrom(app()->getLocale()),
            ]);
        }

        $changes = [];

        if ($user->email_verified_at === null) {
            // Nobody had proven they own this address until now: e.g. someone registered (or changed
            // their profile e-mail to) it with a password of their choosing. The real owner is taking
            // it over, so whatever credentials and sessions that person set up must stop working.
            $changes['email_verified_at'] = now();
            $changes['password'] = null;
            $changes['remember_token'] = Str::random(60);

            if (config('session.driver') === 'database') {
                DB::table(config()->string('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
        }

        if ($googleId !== null && $user->google_id === null) {
            $changes['google_id'] = $googleId;
        }

        if ($changes !== []) {
            $user->forceFill($changes)->save();
        }

        return $user;
    }

    /**
     * Google gives us a real name; for e-mailed codes we start from the address ("ana.silva" -> "Ana Silva")
     * and the person can change it later in their profile.
     */
    private function displayName(?string $name, string $email): string
    {
        if ($name !== null && trim($name) !== '') {
            return Str::limit(trim($name), 255, '');
        }

        return Str::of(Str::before($email, '@'))
            ->replaceMatches('/[._+\-]+/', ' ')
            ->squish()
            ->title()
            ->limit(255, '')
            ->toString() ?: $email;
    }
}
