<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    config([
        'services.google.client_id' => 'test-client-id',
        'services.google.client_secret' => 'test-client-secret',
        'services.google.redirect' => 'http://localhost/auth/google/callback',
    ]);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function fakeGoogleUser(array $overrides = []): void
{
    $raw = array_merge([
        'sub' => 'google-123',
        'name' => 'Ana Silva',
        'email' => 'ana@example.com',
        'email_verified' => true,
    ], $overrides);

    $user = (new SocialiteUser)->setRaw($raw)->map([
        'id' => $raw['sub'],
        'name' => $raw['name'],
        'email' => $raw['email'],
    ]);

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($user);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

test('the routes are hidden while Google is not configured', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('auth.google.redirect'))->assertNotFound();
    $this->get(route('auth.google.callback'))->assertNotFound();
});

test('the login page only offers Google when it is configured', function () {
    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('signInOptions.google', true));

    config(['services.google.client_secret' => null]);

    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('signInOptions.google', false));
});

test('people are sent to Google to sign in', function () {
    $this->get(route('auth.google.redirect'))
        ->assertRedirectContains('accounts.google.com');
});

test('a first sign-in creates a verified, passwordless account', function () {
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->sole();

    $this->assertAuthenticatedAs($user);
    expect($user->name)->toBe('Ana Silva')
        ->and($user->email)->toBe('ana@example.com')
        ->and($user->google_id)->toBe('google-123')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasPassword())->toBeFalse();
});

test('an existing account with the same verified e-mail is linked, not duplicated', function () {
    $existing = User::factory()->create(['email' => 'ana@example.com', 'name' => 'Ana']);
    fakeGoogleUser(['email' => 'ANA@example.com']);

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($existing);
    expect(User::query()->count())->toBe(1)
        ->and($existing->refresh()->google_id)->toBe('google-123')
        ->and($existing->name)->toBe('Ana')
        ->and($existing->hasPassword())->toBeTrue();
});

test('the Google id still matches after the e-mail changes', function () {
    $existing = User::factory()->create(['email' => 'old@example.com', 'google_id' => 'google-123']);
    fakeGoogleUser(['email' => 'new@example.com']);

    $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($existing);
    expect(User::query()->count())->toBe(1);
});

test('an unverified Google e-mail is rejected', function () {
    User::factory()->create(['email' => 'ana@example.com']);
    fakeGoogleUser(['email_verified' => false]);

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(User::query()->whereNotNull('google_id')->exists())->toBeFalse();
});

test('a failed or cancelled Google sign-in goes back to the login page', function () {
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andThrow(new RuntimeException('access_denied'));
    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('after signing in with Google, people land where they were going', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    fakeGoogleUser();

    $this->get(route('auth.google.callback'))->assertRedirect(route('profile.edit'));
});
