<?php

namespace App\Actions\Auth;

use App\Models\LoginCode;
use App\Models\User;
use App\Notifications\LoginCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Issues a fresh one-time code for an e-mail address and mails it.
 *
 * The same thing happens whether or not an account exists, so the endpoint
 * cannot be used to find out who is registered.
 */
final class SendLoginCode
{
    public function handle(string $email): void
    {
        $email = Str::lower(trim($email));
        $code = $this->generateCode();

        DB::transaction(function () use ($email, $code): void {
            // Only the newest code is valid.
            LoginCode::query()->where('email', $email)->whereNull('consumed_at')->delete();

            // No scheduler on free hosting: housekeeping happens here instead.
            LoginCode::query()->where('expires_at', '<', now()->subDay())->delete();

            LoginCode::query()->create([
                'email' => $email,
                'code_hash' => LoginCode::hashFor($email, $code),
                'expires_at' => now()->addMinutes(config()->integer('auth.login_code.ttl_minutes')),
            ]);
        });

        $user = User::query()->where('email', $email)->first();
        $notification = (new LoginCodeNotification($code))->locale($user?->preferredLocale() ?? app()->getLocale());

        if ($user !== null) {
            $user->notify($notification);

            return;
        }

        Notification::route('mail', $email)->notify($notification);
    }

    private function generateCode(): string
    {
        $length = config()->integer('auth.login_code.length');

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
