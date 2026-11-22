<?php

namespace App\Actions\Auth;

use App\Models\LoginCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Checks a one-time code and returns the (possibly new) account it signs in to.
 */
final class VerifyLoginCode
{
    public function __construct(private readonly FindOrCreateVerifiedUser $users) {}

    /**
     * @throws ValidationException when the code is wrong, expired, already used or out of attempts
     */
    public function handle(string $email, string $code): User
    {
        $email = Str::lower(trim($email));

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
            throw ValidationException::withMessages([
                'code' => __('messages.login_code.invalid'),
            ]);
        }

        return $this->users->handle($email);
    }
}
