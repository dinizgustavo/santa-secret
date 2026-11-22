<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('confirm password screen can be rendered', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/confirm-password'));
});

test('password confirmation requires authentication', function () {
    $this->get(route('password.confirm'))->assertRedirect(route('login'));
});
