<?php

use App\Models\GroupExclusion;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the organizer removes a participant and their exclusions go with them', function () {
    [$group, $organizer, [$ana, $bia]] = groupWithMembers(2);
    $group->exclusions()->create(GroupExclusion::orderedPair($ana->id, $bia->id));

    $this->actingAs($organizer)
        ->delete(route('groups.participants.destroy', [$group, $group->participantFor($ana)]))
        ->assertRedirect();

    expect($group->participantFor($ana))->toBeNull()
        ->and($group->exclusions()->count())->toBe(0);
});

test('the organizer cannot be removed and members cannot remove anyone', function () {
    [$group, $organizer, [$ana, $bia]] = groupWithMembers(2);

    $this->actingAs($organizer)
        ->delete(route('groups.participants.destroy', [$group, $group->participantFor($organizer)]))
        ->assertForbidden();

    $this->actingAs($ana)
        ->delete(route('groups.participants.destroy', [$group, $group->participantFor($bia)]))
        ->assertForbidden();
});

test('a participant id from another group is not found', function () {
    [$group, $organizer] = groupWithMembers(1);
    [$other, , [$stranger]] = groupWithMembers(1);

    $this->actingAs($organizer)
        ->delete(route('groups.participants.destroy', [$group, $other->participantFor($stranger)]))
        ->assertNotFound();
});

test('members can leave, the organizer cannot', function () {
    [$group, $organizer, [$ana]] = groupWithMembers(1);

    $this->actingAs($ana)->delete(route('groups.leave', $group))->assertRedirect(route('dashboard'));
    expect($group->participantFor($ana))->toBeNull();

    $this->actingAs($organizer)->delete(route('groups.leave', $group))->assertForbidden();
});

test('after the draw nobody can be removed, leave, or change exclusions', function () {
    [$group, $organizer, [$ana, $bia]] = groupWithMembers(2);
    $exclusion = $group->exclusions()->create(GroupExclusion::orderedPair($ana->id, $bia->id));
    $group->forceFill(['drawn_at' => now()])->save();

    $this->actingAs($organizer)->delete(route('groups.participants.destroy', [$group, $group->participantFor($ana)]))->assertForbidden();
    $this->actingAs($ana)->delete(route('groups.leave', $group))->assertForbidden();
    $this->actingAs($organizer)->post(route('groups.exclusions.store', $group), [
        'participant_a' => $group->participantFor($organizer)?->id,
        'participant_b' => $group->participantFor($ana)?->id,
    ])->assertForbidden();
    $this->actingAs($organizer)->delete(route('groups.exclusions.destroy', [$group, $exclusion]))->assertForbidden();
    $this->actingAs($organizer)->post(route('groups.invite.regenerate', $group))->assertForbidden();

    expect($group->participants()->count())->toBe(3)
        ->and($group->exclusions()->count())->toBe(1);
});

test('exclusions are stored once per pair, in either order', function () {
    [$group, $organizer, [$ana, $bia]] = groupWithMembers(2);
    $a = $group->participantFor($ana);
    $b = $group->participantFor($bia);

    $this->actingAs($organizer)->post(route('groups.exclusions.store', $group), ['participant_a' => $b?->id, 'participant_b' => $a?->id])->assertSessionHasNoErrors();
    $this->actingAs($organizer)->post(route('groups.exclusions.store', $group), ['participant_a' => $a?->id, 'participant_b' => $b?->id])->assertSessionHasNoErrors();

    $exclusion = $group->exclusions()->sole();
    expect([$exclusion->user_a_id, $exclusion->user_b_id])->toBe([min($ana->id, $bia->id), max($ana->id, $bia->id)]);
});

test('an exclusion needs two different people from this group', function () {
    [$group, $organizer, [$ana]] = groupWithMembers(1);
    [$other, , [$stranger]] = groupWithMembers(1);
    $a = $group->participantFor($ana);

    $this->actingAs($organizer)
        ->post(route('groups.exclusions.store', $group), ['participant_a' => $a?->id, 'participant_b' => $a?->id])
        ->assertSessionHasErrors('participant_b');

    $this->actingAs($organizer)
        ->post(route('groups.exclusions.store', $group), ['participant_a' => $a?->id, 'participant_b' => $other->participantFor($stranger)?->id])
        ->assertSessionHasErrors('participant_b');

    expect($group->exclusions()->count())->toBe(0);
});

test('only the organizer manages exclusions', function () {
    [$group, $organizer, [$ana, $bia]] = groupWithMembers(2);

    $this->actingAs($ana)->post(route('groups.exclusions.store', $group), [
        'participant_a' => $group->participantFor($organizer)?->id,
        'participant_b' => $group->participantFor($bia)?->id,
    ])->assertForbidden();
});

test('the organizer sets and clears a gift rule for a participant', function () {
    [$group, $organizer, [$ana]] = groupWithMembers(1);
    $participant = $group->participantFor($ana);

    $this->actingAs($organizer)
        ->put(route('groups.participants.gift-rule', [$group, $participant]), ['gift_rule' => '  Chocolate, please  '])
        ->assertRedirect();
    expect($participant?->refresh()->gift_rule)->toBe('Chocolate, please');

    $this->actingAs($organizer)
        ->put(route('groups.participants.gift-rule', [$group, $participant]), ['gift_rule' => ''])
        ->assertRedirect();
    expect($participant?->refresh()->gift_rule)->toBeNull();
});

test('members cannot set gift rules', function () {
    [$group, , [$ana]] = groupWithMembers(1);

    $this->actingAs($ana)
        ->put(route('groups.participants.gift-rule', [$group, $group->participantFor($ana)]), ['gift_rule' => 'Anything'])
        ->assertForbidden();
});

test('gift rules are only shown to the organizer and to the person they belong to', function () {
    [$group, $organizer, [$ana, $bia]] = groupWithMembers(2);
    $group->participantFor($ana)?->update(['gift_rule' => 'Chocolate']);

    $this->actingAs($organizer)->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page) => $page->where('participants.1.gift_rule', 'Chocolate'));

    $this->actingAs($ana)->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page) => $page
            ->where('me.gift_rule', 'Chocolate')
            ->where('participants.1.gift_rule', 'Chocolate'));

    $this->actingAs($bia)->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('participants.1.gift_rule')
            ->where('me.gift_rule', null))
        ->assertDontSee('Chocolate');
});

test('people in a drawn group cannot delete their account', function () {
    [$group, , [$ana]] = groupWithMembers(2);
    $group->forceFill(['drawn_at' => now()])->save();

    $this->actingAs($ana)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasErrors('password');

    expect($ana->fresh())->not->toBeNull();
});

test('people in groups that were not drawn yet can delete their account', function () {
    [$group, , [$ana]] = groupWithMembers(2);

    $this->actingAs($ana)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect(route('home'));

    expect(User::query()->find($ana->id))->toBeNull()
        ->and($group->participants()->count())->toBe(2);
});
