<?php

use App\Actions\Auth\VerifyLoginCode;
use App\Enums\Locale;
use App\Http\Middleware\SetLocale;
use App\Models\LoginCode;
use App\Models\User;
use App\Notifications\LoginCodeNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    config(['auth.login_code.enabled' => true]);
    Notification::fake();
});

/**
 * Requests a code for the address and returns the plain code that was e-mailed.
 */
function requestLoginCode(string $email): string
{
    test()->post(route('login-code.store'), ['email' => $email])
        ->assertRedirect(route('login-code.edit'));

    $code = null;

    Notification::assertSentOnDemand(LoginCodeNotification::class, function (LoginCodeNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($email, &$code) {
        $code = $notification->code;

        return $notifiable->routes['mail'] === $email;
    });

    return $code ?? throw new RuntimeException('No code was sent.');
}

test('the routes are hidden while the feature is disabled', function () {
    config(['auth.login_code.enabled' => false]);

    $this->get(route('login-code.create'))->assertNotFound();
    $this->post(route('login-code.store'), ['email' => 'ana@example.com'])->assertNotFound();
});

test('the feature turns on automatically with a real mailer', function () {
    config(['auth.login_code.enabled' => null, 'mail.default' => 'log']);
    $this->get(route('login-code.create'))->assertNotFound();

    config(['mail.default' => 'smtp']);
    $this->get(route('login-code.create'))->assertOk();
});

test('a code is e-mailed and only its hash is stored', function () {
    $code = requestLoginCode('ana@example.com');

    $stored = LoginCode::query()->sole();

    expect($code)->toMatch('/^\d{6}$/')
        ->and($stored->email)->toBe('ana@example.com')
        ->and($stored->code_hash)->not->toContain($code)
        ->and($stored->code_hash)->toBe(LoginCode::hashFor('ana@example.com', $code));
});

test('existing users receive the code in their own language', function () {
    $user = User::factory()->create(['locale' => Locale::Spanish]);

    $this->post(route('login-code.store'), ['email' => $user->email]);

    Notification::assertSentTo($user, LoginCodeNotification::class, fn ($notification, $channels, $notifiable, $locale) => $locale === 'es');
});

test('the response does not reveal whether an account exists', function () {
    $user = User::factory()->create();

    $known = $this->post(route('login-code.store'), ['email' => $user->email]);
    $unknown = $this->post(route('login-code.store'), ['email' => 'nobody@example.com']);

    expect($known->getStatusCode())->toBe($unknown->getStatusCode())
        ->and($known->headers->get('Location'))->toBe($unknown->headers->get('Location'));
});

test('a new account is created and signed in with the right code', function () {
    $code = requestLoginCode('ana.silva@example.com');

    $this->withSession([SetLocale::SESSION_KEY => 'es'])
        ->post(route('login-code.verify'), ['code' => $code])
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'ana.silva@example.com')->sole();

    $this->assertAuthenticatedAs($user);
    expect($user->name)->toBe('Ana Silva')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasPassword())->toBeFalse()
        ->and($user->locale)->toBe(Locale::Spanish);
});

test('an existing account is signed in with the right code', function () {
    $user = User::factory()->create(['email' => 'bia@example.com']);

    $this->post(route('login-code.store'), ['email' => 'BIA@example.com'])
        ->assertRedirect(route('login-code.edit'));

    $code = null;
    Notification::assertSentTo($user, LoginCodeNotification::class, function (LoginCodeNotification $notification) use (&$code) {
        $code = $notification->code;

        return true;
    });

    $this->post(route('login-code.verify'), ['code' => $code]);

    $this->assertAuthenticatedAs($user);
    expect(User::query()->count())->toBe(1);
});

test('a wrong code is rejected and counts as an attempt', function () {
    $code = requestLoginCode('ana@example.com');
    $wrong = $code === '000000' ? '111111' : '000000';

    $this->post(route('login-code.verify'), ['code' => $wrong])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
    expect(LoginCode::query()->sole()->attempts)->toBe(1);
});

test('the code stops working after too many attempts', function () {
    $code = requestLoginCode('ana@example.com');
    LoginCode::query()->update(['attempts' => config('auth.login_code.max_attempts')]);

    $this->post(route('login-code.verify'), ['code' => $code])->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('an expired code is rejected', function () {
    $code = requestLoginCode('ana@example.com');

    $this->travel(config('auth.login_code.ttl_minutes') + 1)->minutes();

    $this->post(route('login-code.verify'), ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

test('a code can only be used once', function () {
    $code = requestLoginCode('ana@example.com');

    $this->post(route('login-code.verify'), ['code' => $code]);
    $this->assertAuthenticated();

    auth()->logout();

    $this->withSession(['login_code.email' => 'ana@example.com'])
        ->post(route('login-code.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');
    $this->assertGuest();
});

test('requesting a new code invalidates the previous one', function () {
    $first = requestLoginCode('ana@example.com');
    Notification::fake();
    $second = requestLoginCode('ana@example.com');

    expect(LoginCode::query()->count())->toBe(1);

    if ($first !== $second) {
        $this->post(route('login-code.verify'), ['code' => $first])->assertSessionHasErrors('code');
    }

    $this->post(route('login-code.verify'), ['code' => $second]);
    $this->assertAuthenticated();
});

test('wrong codes are capped per address across new codes', function () {
    $code = requestLoginCode('ana@example.com');
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, VerifyLoginCode::MAX_FAILURES_PER_HOUR) as $ignored) {
        LoginCode::query()->update(['attempts' => 0]); // as if a fresh code had been requested
        $this->post(route('login-code.verify'), ['code' => $wrong])->assertSessionHasErrors('code');
    }

    // Even the right code is refused now, until the hour passes.
    $this->post(route('login-code.verify'), ['code' => $code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

test('an address can only receive a few codes per hour', function () {
    foreach (range(1, 5) as $minute) {
        $this->travel(1)->minutes();
        $this->post(route('login-code.store'), ['email' => 'ana@example.com'])->assertRedirect();
    }

    $this->travel(1)->minutes();
    $this->post(route('login-code.store'), ['email' => 'ana@example.com'])->assertTooManyRequests();
});

test('old codes are cleaned up when new ones are sent', function () {
    requestLoginCode('old@example.com');
    LoginCode::query()->update(['expires_at' => now()->subDays(2), 'consumed_at' => now()->subDays(2)]);

    requestLoginCode('new@example.com');

    expect(LoginCode::query()->pluck('email')->all())->toBe(['new@example.com']);
});

test('the verify page needs an e-mail in the session', function () {
    $this->get(route('login-code.edit'))->assertRedirect(route('login-code.create'));
    $this->post(route('login-code.verify'), ['code' => '123456'])->assertRedirect(route('login-code.create'));
});

test('sending codes is rate limited per address', function () {
    foreach (range(1, 3) as $ignored) {
        $this->post(route('login-code.store'), ['email' => 'ana@example.com'])->assertRedirect();
    }

    $this->post(route('login-code.store'), ['email' => 'ana@example.com'])->assertTooManyRequests();
});

test('after signing in, people land where they were going', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));

    $code = requestLoginCode('ana@example.com');

    $this->post(route('login-code.verify'), ['code' => $code])
        ->assertRedirect(route('profile.edit'));
});
