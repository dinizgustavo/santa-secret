<?php

use App\Enums\Currency;
use App\Enums\ParticipantRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('a user creates a group and becomes its organizer', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('groups.store'), [
        'name' => 'Natal da família',
        'description' => 'Troca no dia 24',
        'budget_max' => '49,90',
        'budget_currency' => 'BRL',
        'event_date' => now()->addWeeks(3)->toDateString(),
    ]);

    $group = Group::query()->sole();

    $response->assertRedirect(route('groups.show', $group));
    expect($group->budget_max_amount)->toBe(4990)
        ->and($group->budget_currency)->toBe(Currency::BRL)
        ->and($group->ulid)->toHaveLength(26)
        ->and($group->invite_token)->toHaveLength(40)
        ->and($group->participants()->sole()->only(['user_id', 'role']))->toBe(['user_id' => $user->id, 'role' => ParticipantRole::Organizer]);
});

test('today in a time zone behind UTC is still accepted', function () {
    // 01:00 UTC on Dec 2 is still Dec 1 in Brazil (UTC-3).
    $this->travelTo(now()->setDate(2026, 12, 2)->setTime(1, 0));

    $this->actingAs(User::factory()->create())
        ->post(route('groups.store'), ['name' => 'Hoje', 'event_date' => '2026-12-01'])
        ->assertSessionHasNoErrors();
});

test('the budget is optional but needs a currency when given', function () {
    $user = User::factory()->create();
    $date = now()->addWeek()->toDateString();

    $this->actingAs($user)
        ->post(route('groups.store'), ['name' => 'Sem limite', 'event_date' => $date])
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('groups.store'), ['name' => 'Com limite', 'event_date' => $date, 'budget_max' => '50'])
        ->assertSessionHasErrors('budget_currency');

    expect(Group::query()->sole()->budget_max_amount)->toBeNull();
});

test('group fields are validated', function (array $payload, string $field) {
    $this->actingAs(User::factory()->create())
        ->post(route('groups.store'), array_merge([
            'name' => 'Grupo',
            'event_date' => now()->addWeek()->toDateString(),
        ], $payload))
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'past date' => [['event_date' => now()->subDays(2)->toDateString()], 'event_date'],
    'negative budget' => [['budget_max' => '-5', 'budget_currency' => 'BRL'], 'budget_max'],
    'three decimals' => [['budget_max' => '10.555', 'budget_currency' => 'BRL'], 'budget_max'],
    'unknown currency' => [['budget_max' => '10', 'budget_currency' => 'XYZ'], 'budget_currency'],
]);

test('the dashboard lists only the groups the user belongs to', function () {
    [$mine, $organizer] = groupWithMembers(2);
    groupWithMembers(1);

    $this->actingAs($organizer)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('groups', 1)
            ->where('groups.0.id', $mine->ulid)
            ->where('groups.0.participants_count', 3)
            ->where('groups.0.is_organizer', true),
        );
});

test('the dashboard does not run more queries as the number of groups grows', function () {
    $user = User::factory()->create();

    $queriesFor = function () use ($user): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        return count(DB::getQueryLog());
    };

    Group::factory()->organizedBy($user, User::factory()->create())->create();
    $withOne = $queriesFor();

    Group::factory()->count(5)->organizedBy($user, User::factory()->create())->create();
    $withSix = $queriesFor();

    expect($withSix)->toBe($withOne);
});

test('members can see the group page and outsiders cannot', function () {
    [$group, , [$member]] = groupWithMembers(1);

    $this->actingAs($member)->get(route('groups.show', $group))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('groups.show', $group))->assertForbidden();
});

test('groups are addressed by their ULID, not their numeric id', function () {
    [$group, $organizer] = groupWithMembers(1);

    $this->actingAs($organizer)->get('/groups/'.$group->id)->assertNotFound();
    $this->actingAs($organizer)->get('/groups/'.$group->ulid)->assertOk();
});

test('only the organizer sees the invite link and the exclusions', function () {
    [$group, $organizer, [$member]] = groupWithMembers(2);

    $this->actingAs($organizer)
        ->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page) => $page
            ->where('invite_url', route('invites.show', $group->invite_token))
            ->where('can.manage_exclusions', true),
        );

    $this->actingAs($member)
        ->get(route('groups.show', $group))
        ->assertInertia(fn (Assert $page) => $page
            ->where('invite_url', null)
            ->where('exclusions', [])
            ->where('can.manage_exclusions', false)
            ->where('can.leave', true),
        );
});

test('only the organizer can edit or delete the group', function () {
    [$group, $organizer, [$member]] = groupWithMembers(1);
    $payload = ['name' => 'Novo nome', 'event_date' => now()->addWeek()->toDateString()];

    $this->actingAs($member)->get(route('groups.edit', $group))->assertForbidden();
    $this->actingAs($member)->put(route('groups.update', $group), $payload)->assertForbidden();
    $this->actingAs($member)->delete(route('groups.destroy', $group))->assertForbidden();

    $this->actingAs($organizer)->put(route('groups.update', $group), $payload)->assertRedirect(route('groups.show', $group));
    expect($group->refresh()->name)->toBe('Novo nome');

    $this->actingAs($organizer)->delete(route('groups.destroy', $group))->assertRedirect(route('dashboard'));
    expect(Group::query()->count())->toBe(0);
});

test('the database allows a single organizer per group', function () {
    [$group, , [$member]] = groupWithMembers(1);

    expect(fn () => $group->participants()->where('user_id', $member->id)->update(['role' => 'organizer']))
        ->toThrow(UniqueConstraintViolationException::class);
});
