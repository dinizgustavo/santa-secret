<?php

use App\Enums\Locale;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['app.locale' => 'pt_BR', 'app.fallback_locale' => 'en']);
});

test('the default locale is used when nothing else is known', function () {
    // Laravel's test client sends "Accept-Language: en-us" by default, so clear it.
    $this->withHeader('Accept-Language', '')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale.current', 'pt_BR')
            ->where('locale.bcp47', 'pt-BR')
            ->where('translations', fn ($translations) => $translations['common.app_name'] === 'Amigo Secreto'),
        );
});

test('the Accept-Language header picks a supported locale', function (string $header, string $expected) {
    $this->withHeader('Accept-Language', $header)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale.current', $expected));
})->with([
    'Spanish (Spain)' => ['es-ES,es;q=0.9', 'es'],
    'English (US)' => ['en-US,en;q=0.8', 'en'],
    'Brazilian Portuguese' => ['pt-BR,pt;q=0.9', 'pt_BR'],
    'Portuguese from Portugal maps to pt_BR' => ['pt-PT', 'pt_BR'],
    'Spanish (Mexico) maps to es' => ['es-MX', 'es'],
    'first supported language wins' => ['fr-FR,de;q=0.9,es;q=0.8,en;q=0.7', 'es'],
    'unsupported falls back to default' => ['fr-FR,fr;q=0.9', 'pt_BR'],
]);

test('the session wins over the Accept-Language header', function () {
    $this->withSession([SetLocale::SESSION_KEY => 'en'])
        ->withHeader('Accept-Language', 'es-ES')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale.current', 'en'));
});

test('an invalid session value is ignored', function () {
    $this->withSession([SetLocale::SESSION_KEY => 'xx'])
        ->withHeader('Accept-Language', 'es-ES')
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('locale.current', 'es'));
});

test('the saved user preference wins over everything else', function () {
    $user = User::factory()->create(['locale' => Locale::Spanish]);

    $this->actingAs($user)
        ->withSession([SetLocale::SESSION_KEY => 'en'])
        ->withHeader('Accept-Language', 'en-US')
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale.current', 'es')
            ->where('translations', fn ($translations) => $translations['nav.dashboard'] === 'Inicio'),
        );
});

test('guests can switch the language for their session', function () {
    $this->from(route('login'))
        ->put(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect(route('login'))
        ->assertSessionHas(SetLocale::SESSION_KEY, 'en');

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('locale.current', 'en'));
});

test('signed-in users get their language saved to their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->put(route('locale.update'), ['locale' => 'es'])
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->locale)->toBe(Locale::Spanish);
});

test('unsupported locales are rejected', function () {
    $this->from(route('home'))
        ->put(route('locale.update'), ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

test('validation messages follow the active locale', function () {
    $expected = trans('validation.required', ['attribute' => trans('validation.attributes.email', [], 'es')], 'es');

    expect($expected)->not->toBe(trans('validation.required', ['attribute' => 'email'], 'en'));

    $this->withSession([SetLocale::SESSION_KEY => 'es'])
        ->from(route('login'))
        ->post(route('login.store'), ['email' => '', 'password' => ''])
        ->assertSessionHasErrors(['email' => $expected]);
});

test('notifications are sent in the user\'s preferred language', function () {
    Notification::fake();
    $user = User::factory()->create(['locale' => Locale::Spanish]);

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo(
        $user,
        ResetPassword::class,
        fn (ResetPassword $notification, array $channels, User $notifiable, ?string $locale) => $locale === 'es',
    );
});

test('only whitelisted user fields are shared with the frontend', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ]),
        );
});
