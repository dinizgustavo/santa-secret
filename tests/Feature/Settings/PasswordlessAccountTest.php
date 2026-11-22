<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('passwordless accounts cannot sign in with an empty password', function () {
    $user = User::factory()->passwordless()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => ''])
        ->assertSessionHasErrors();

    $this->assertGuest();
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
