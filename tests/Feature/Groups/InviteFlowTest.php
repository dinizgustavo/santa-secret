<?php

use App\Models\Group;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests opening an invite are asked to log in and come back to it', function () {
    [$group] = groupWithMembers(1);
    $friend = User::factory()->create();
    $invite = route('invites.show', $group->invite_token);

    $this->get($invite)->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => $friend->email, 'password' => 'password'])
        ->assertRedirect($invite);
});

test('opening the invite shows the group without joining it', function () {
    [$group, $organizer] = groupWithMembers(1);
    $friend = User::factory()->create();

    $this->actingAs($friend)
        ->get(route('invites.show', $group->invite_token))
        ->assertInertia(fn (Assert $page) => $page
            ->component('invites/show')
            ->where('group.name', $group->name)
            ->where('group.organizer_name', $organizer->name)
            ->where('group.participants_count', 2)
            ->where('group.is_drawn', false),
        );

    expect($group->participantFor($friend))->toBeNull();
});

test('accepting the invite joins the group', function () {
    [$group] = groupWithMembers(1);
    $friend = User::factory()->create();

    $this->actingAs($friend)
        ->post(route('invites.accept', $group->invite_token))
        ->assertRedirect(route('groups.show', $group));

    expect($group->participantFor($friend)?->isOrganizer())->toBeFalse();
});

test('joining twice is harmless and members are sent to the group page', function () {
    [$group, , [$member]] = groupWithMembers(1);

    $this->actingAs($member)->post(route('invites.accept', $group->invite_token))
        ->assertInertiaFlashMissing('toast');
    $this->actingAs($member)->get(route('invites.show', $group->invite_token))->assertRedirect(route('groups.show', $group));

    expect($group->participants()->count())->toBe(2);
});

test('an unknown token is a 404', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('invites.show', 'not-a-real-token'))
        ->assertNotFound();
});

test('the organizer can regenerate the link and the old one stops working', function () {
    [$group, $organizer] = groupWithMembers(1);
    $oldToken = $group->invite_token;

    $this->actingAs($organizer)->post(route('groups.invite.regenerate', $group))->assertRedirect();

    expect($group->refresh()->invite_token)->not->toBe($oldToken);
    $this->actingAs(User::factory()->create())->get(route('invites.show', $oldToken))->assertNotFound();
});

test('members cannot regenerate the invite link', function () {
    [$group, , [$member]] = groupWithMembers(1);

    $this->actingAs($member)->post(route('groups.invite.regenerate', $group))->assertForbidden();
});

test('nobody can join after the draw', function () {
    [$group] = groupWithMembers(2);
    $group->forceFill(['drawn_at' => now()])->save();
    $late = User::factory()->create();

    $this->actingAs($late)
        ->from(route('invites.show', $group->invite_token))
        ->post(route('invites.accept', $group->invite_token))
        ->assertRedirect(route('invites.show', $group->invite_token));

    expect($group->participantFor($late))->toBeNull();
});

test('a full group cannot be joined', function () {
    [$group] = groupWithMembers(1);
    $group->participants()->createMany(
        User::factory()->count(Group::MAX_PARTICIPANTS - 2)->create()
            ->map(fn (User $user) => ['user_id' => $user->id, 'role' => 'participant', 'joined_at' => now()])
            ->all(),
    );
    $late = User::factory()->create();

    $this->actingAs($late)->post(route('invites.accept', $group->invite_token));

    expect($group->participantFor($late))->toBeNull()
        ->and($group->participants()->count())->toBe(Group::MAX_PARTICIPANTS);
});

test('joining is rate limited', function () {
    $user = User::factory()->create();

    foreach (range(1, 10) as $ignored) {
        $this->actingAs($user)->post(route('invites.accept', 'missing'))->assertNotFound();
    }

    $this->actingAs($user)->post(route('invites.accept', 'missing'))->assertTooManyRequests();
});
