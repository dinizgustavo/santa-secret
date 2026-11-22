<?php

use App\Actions\Auth\FindOrCreateVerifiedUser;
use App\Models\Group;
use App\Models\User;
use App\Notifications\LoginCodeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('passwordless accounts cannot sign in with any password', function (string $password) {
    $user = User::factory()->passwordless()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => $password])
        ->assertSessionHasErrors();

    $this->assertGuest();
})->with(['empty' => '', 'a guess' => 'password', 'another guess' => 'null']);

test('proving ownership of an unverified account locks out whoever set its password', function () {
    // Someone registered Ana's address with a password of their own; she never verified it.
    $squatted = User::factory()->unverified()->create(['email' => 'ana@example.com']);
    $squatted->forceFill(['remember_token' => 'old-token'])->save();
    config(['auth.login_code.enabled' => true]);
    Notification::fake();

    $this->post(route('login-code.store'), ['email' => 'ana@example.com']);
    $code = null;
    Notification::assertSentTo($squatted, LoginCodeNotification::class, function ($notification) use (&$code) {
        $code = $notification->code;

        return true;
    });
    $this->post(route('login-code.verify'), ['code' => $code]);

    $this->assertAuthenticatedAs($squatted);
    $squatted->refresh();
    expect($squatted->hasPassword())->toBeFalse()
        ->and($squatted->remember_token)->not->toBe('old-token')
        ->and($squatted->email_verified_at)->not->toBeNull();

    auth()->logout();
    $this->post(route('login.store'), ['email' => 'ana@example.com', 'password' => 'password']);
    $this->assertGuest();
});

test('verified accounts keep their password when they also use a code or Google', function () {
    $user = User::factory()->create(['email' => 'bia@example.com']);

    app(FindOrCreateVerifiedUser::class)->handle('bia@example.com', googleId: 'google-1');

    expect($user->refresh()->hasPassword())->toBeTrue()
        ->and($user->google_id)->toBe('google-1');
});

test('deleting an organizer account deletes the groups they organize', function () {
    [$group, $organizer, [$ana]] = groupWithMembers(2);
    [$other] = groupWithMembers(1);
    $other->participants()->create(['user_id' => $organizer->id, 'role' => 'participant', 'joined_at' => now()]);

    $this->actingAs($organizer)
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('home'));

    expect(Group::query()->find($group->id))->toBeNull()
        ->and($other->refresh()->participants()->count())->toBe(2)
        ->and($ana->fresh())->not->toBeNull();
});

test('passwordless accounts can set a password without a current one', function () {
    $user = User::factory()->passwordless()->create();

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('new-password', (string) $user->refresh()->password))->toBeTrue();
});

test('accounts with a password still need the current one', function () {
    $this->actingAs(User::factory()->create())
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('current_password');
});

test('passwordless accounts confirm deletion by typing their e-mail', function () {
    $user = User::factory()->passwordless()->create(['email' => 'ana@example.com']);

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['confirmation' => 'wrong@example.com'])
        ->assertSessionHasErrors('confirmation');

    expect($user->fresh())->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), ['confirmation' => ' ANA@example.com '])
        ->assertRedirect(route('home'));

    expect($user->fresh())->toBeNull();
});
