<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

test('the health check responds without a session', function () {
    $this->get('/up')->assertOk();
});

test('https from the proxy is respected for generated urls and redirects', function () {
    $this->withHeaders([
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'secret-santa.example.com',
        'X-Forwarded-Port' => '443',
    ])->get(route('dashboard', absolute: false))
        ->assertRedirect('https://secret-santa.example.com/login');
});

test('invite links shared in production use https', function () {
    config(['app.url' => 'https://secret-santa.example.com']);
    URL::forceRootUrl('https://secret-santa.example.com');
    URL::forceScheme('https');

    [$group, $organizer] = groupWithMembers(1);

    $this->actingAs($organizer)
        ->get(route('groups.show', $group))
        ->assertInertia(fn ($page) => $page->where('invite_url', fn (string $url) => str_starts_with($url, 'https://secret-santa.example.com/invites/')));
});

test('link previews are translated and never mention a group', function () {
    [$group] = groupWithMembers(1);
    $this->app['auth']->forgetGuards();

    $this->withHeader('Accept-Language', 'es-ES')
        ->get(route('invites.show', $group->invite_token))
        ->assertRedirect(route('login'));

    $this->withHeader('Accept-Language', 'es-ES')
        ->get(route('login'))
        ->assertSee('<html lang="es"', false)
        ->assertSee('<meta property="og:site_name" content="Amigo Invisible">', false)
        ->assertDontSee($group->name);
});

test('sessions and cache use the database by default so nothing depends on local disk', function () {
    $example = file_get_contents(base_path('.env.example'));

    expect($example)
        ->toContain('SESSION_DRIVER=database')
        ->toContain('CACHE_STORE=database')
        ->toContain('QUEUE_CONNECTION=sync')
        ->toContain('MAIL_MAILER=log');
});

test('with the log mailer the app hides e-mailed codes but password sign-in still works', function () {
    config(['mail.default' => 'log', 'auth.login_code.enabled' => null]);
    $user = User::factory()->create();

    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('signInOptions.loginCode', false));
    $this->get(route('login-code.create'))->assertNotFound();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
});
