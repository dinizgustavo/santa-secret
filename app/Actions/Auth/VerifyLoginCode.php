<?php

namespace App\Actions\Auth;

use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Checks a one-time code and returns the (possibly new) account it signs in to.
 */
final class VerifyLoginCode
{
    /** Wrong codes allowed per address per hour, across every code sent in that hour. */
    public const MAX_FAILURES_PER_HOUR = 10;

    public function __construct(private readonly FindOrCreateVerifiedUser $users) {}

    /**
     * @throws ValidationException when the code is wrong, expired, already used or out of attempts
     */
    public function handle(string $email, string $code): User
    {
        $email = Str::lower(trim($email));
        $failuresKey = self::failuresKey($email);

        // Per-code attempts reset whenever a new code is requested, so also cap failures per
        // address across codes. Otherwise "request a code, try 5 times, repeat" adds up.
        if (RateLimiter::tooManyAttempts($failuresKey, self::MAX_FAILURES_PER_HOUR)) {
            throw ValidationException::withMessages([
                'code' => __('messages.login_code.invalid'),
            ]);
        }

        $verified = DB::transaction(function () use ($email, $code): bool {
            // Lock the row so two parallel requests cannot both consume the same code.
            $loginCode = LoginCode::query()
                ->where('email', $email)
                ->usable()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($loginCode === null || $loginCode->attempts >= config()->integer('auth.login_code.max_attempts')) {
                return false;
            }

            if (! hash_equals($loginCode->code_hash, LoginCode::hashFor($email, $code))) {
                $loginCode->increment('attempts');

                return false;
            }

            $loginCode->forceFill(['consumed_at' => now()])->save();

            return true;
        });

        if (! $verified) {
            RateLimiter::hit($failuresKey, 3600);

            throw ValidationException::withMessages([
                'code' => __('messages.login_code.invalid'),
            ]);
        }

        RateLimiter::clear($failuresKey);

        return $this->users->handle($email);
    }

    public static function failuresKey(string $email): string
    {
        return 'login-code-failures|'.Str::lower(trim($email));
    }
}
