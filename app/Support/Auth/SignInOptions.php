<?php

namespace App\Support\Auth;

/**
 * Which sign-in methods are available, based on configuration.
 *
 * Password sign-in is always on. Google needs OAuth credentials, and e-mailed
 * codes need a mailer that actually delivers mail.
 */
final class SignInOptions
{
    public static function google(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public static function loginCode(): bool
    {
        $explicit = config('auth.login_code.enabled');

        if ($explicit !== null && $explicit !== '') {
            return filter_var($explicit, FILTER_VALIDATE_BOOLEAN);
        }

        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }

    /**
     * @return array{google: bool, loginCode: bool}
     */
    public static function toArray(): array
    {
        return [
            'google' => self::google(),
            'loginCode' => self::loginCode(),
        ];
    }
}
