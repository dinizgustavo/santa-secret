<?php

use App\Draw\DrawSolver;
use App\Models\Assignment;
use App\Models\Group;
use App\Models\GroupExclusion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Random\Engine\Mt19937;
use Random\Randomizer;

test('the organizer runs the draw and every member gives and receives exactly once', function () {
    [$group, $organizer] = groupWithMembers(5);

    $this->actingAs($organizer)
        ->post(route('groups.draw.store', $group))
        ->assertRedirect(route('groups.show', $group));

    $group->refresh();
    $members = $group->participants()->pluck('user_id')->sort()->values()->all();
    $assignments = Assignment::query()->where('group_id', $group->id)->get();

    expect($group->isDrawn())->toBeTrue()
        ->and($assignments)->toHaveCount(6)
        ->and($assignments->pluck('giver_id')->sort()->values()->all())->toBe($members)
        ->and($assignments->pluck('receiver_id')->sort()->values()->all())->toBe($members)
        ->and($assignments->filter(fn (Assignment $a) => $a->giver_id === $a->receiver_id))->toBeEmpty();
});

test('the stored draw respects the exclusions', function () {
    [$group, $organizer, [$ana, $bia, $caio, $duda]] = groupWithMembers(4);
    $group->exclusions()->create(GroupExclusion::orderedPair($ana->id, $bia->id));
    $group->exclusions()->create(GroupExclusion::orderedPair($caio->id, $duda->id));

    $this->actingAs($organizer)->post(route('groups.draw.store', $group));

    $pairs = Assignment::query()->where('group_id', $group->id)->pluck('receiver_id', 'giver_id');

    expect($pairs[$ana->id])->not->toBe($bia->id)
        ->and($pairs[$bia->id])->not->toBe($ana->id)
        ->and($pairs[$caio->id])->not->toBe($duda->id)
        ->and($pairs[$duda->id])->not->toBe($caio->id);
});

test('a seeded engine makes the stored draw reproducible', function () {
    $drawWithSeed = function (int $seed): array {
        [$group, $organizer] = groupWithMembers(6);
        app()->instance(DrawSolver::class, new DrawSolver(new Randomizer(new Mt19937($seed))));

        $this->actingAs($organizer)->post(route('groups.draw.store', $group));

        // Compare by join order, since user ids differ between the two groups.
        $order = $group->participants()->orderBy('id')->pluck('user_id')->flip();

        return Assignment::query()->where('group_id', $group->id)->get()
            ->mapWithKeys(fn (Assignment $a) => [$order[$a->giver_id] => $order[$a->receiver_id]])
            ->sortKeys()
            ->all();
    };

    expect($drawWithSeed(1234))->toBe($drawWithSeed(1234));
});

test('only the organizer can run the draw', function () {
    [$group, , [$ana, $bia]] = groupWithMembers(3);

    $this->actingAs($ana)->post(route('groups.draw.store', $group))->assertForbidden();
    $this->actingAs(User::factory()->create())->post(route('groups.draw.store', $group))->assertForbidden();

    expect($group->refresh()->isDrawn())->toBeFalse();
});

test('the draw needs at least three participants', function () {
    [$group, $organizer] = groupWithMembers(1);

    $this->actingAs($organizer)
        ->from(route('groups.show', $group))
        ->post(route('groups.draw.store', $group))
        ->assertRedirect(route('groups.show', $group))
        ->assertInertiaFlash('toast.type', 'error');

    expect($group->refresh()->isDrawn())->toBeFalse()
        ->and(Assignment::query()->count())->toBe(0);
});

test('an impossible draw saves nothing and explains why', function () {
    [$group, $organizer, [$ana, $bia]] = groupWithMembers(2);
    // Three people where the organizer may draw nobody.
    $group->exclusions()->create(GroupExclusion::orderedPair($organizer->id, $ana->id));
    $group->exclusions()->create(GroupExclusion::orderedPair($organizer->id, $bia->id));

    $this->actingAs($organizer)
        ->from(route('groups.show', $group))
        ->post(route('groups.draw.store', $group))
        ->assertRedirect(route('groups.show', $group))
        ->assertInertiaFlash('toast.message', __('groups.errors.impossible_draw'));

    expect($group->refresh()->isDrawn())->toBeFalse()
        ->and(Assignment::query()->count())->toBe(0);
});

test('the draw runs only once', function () {
    [$group, $organizer] = groupWithMembers(3);

    $this->actingAs($organizer)->post(route('groups.draw.store', $group));
    $first = Assignment::query()->orderBy('giver_id')->pluck('receiver_id', 'giver_id')->all();

    $this->actingAs($organizer)->post(route('groups.draw.store', $group))->assertForbidden();

    expect(Assignment::query()->orderBy('giver_id')->pluck('receiver_id', 'giver_id')->all())->toBe($first);
});

test('the organizer can reset the draw, which unlocks the group', function () {
    [$group, $organizer] = groupWithMembers(3);
    $this->actingAs($organizer)->post(route('groups.draw.store', $group));

    $this->actingAs($organizer)
        ->delete(route('groups.draw.destroy', $group))
        ->assertRedirect(route('groups.show', $group));

    expect($group->refresh()->isDrawn())->toBeFalse()
        ->and(Assignment::query()->count())->toBe(0);

    $late = User::factory()->create();
    $this->actingAs($late)->post(route('invites.accept', $group->invite_token));
    expect($group->participantFor($late))->not->toBeNull();
});

test('only the organizer can reset the draw', function () {
    [$group, $organizer, [$ana]] = groupWithMembers(3);
    $this->actingAs($organizer)->post(route('groups.draw.store', $group));

    $this->actingAs($ana)->delete(route('groups.draw.destroy', $group))->assertForbidden();

    expect($group->refresh()->isDrawn())->toBeTrue();
});

test('the database refuses to drop a participant who is part of a draw', function () {
    [$group, $organizer, [$ana]] = groupWithMembers(3);
    $this->actingAs($organizer)->post(route('groups.draw.store', $group));

    expect(fn () => $group->participants()->where('user_id', $ana->id)->delete())
        ->toThrow(QueryException::class);
});

test('deleting a drawn group removes its assignments too', function () {
    [$group, $organizer] = groupWithMembers(3);
    $this->actingAs($organizer)->post(route('groups.draw.store', $group));

    $this->actingAs($organizer)->delete(route('groups.destroy', $group));

    expect(Group::query()->count())->toBe(0)
        ->and(Assignment::query()->count())->toBe(0);
});

test('running the draw is rate limited', function () {
    [$group, , [$ana]] = groupWithMembers(3);

    foreach (range(1, 5) as $ignored) {
        $this->actingAs($ana)->post(route('groups.draw.store', $group))->assertForbidden();
    }

    $this->actingAs($ana)->post(route('groups.draw.store', $group))->assertTooManyRequests();
});
