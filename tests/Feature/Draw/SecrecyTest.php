<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Assignment;
use App\Models\Group;
use App\Models\User;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/*
| Secrecy is the core requirement: a person can only ever see their own match,
| the organizer included, through any route.
*/

/**
 * A drawn group with distinctive names so leaks are easy to spot in raw responses.
 *
 * @return array{0: Group, 1: User, 2: list<User>, 3: array<int, int>} group, organizer, members, giver id => receiver id
 */
function drawnGroup(): array
{
    $organizer = User::factory()->create(['name' => 'Olga Organizer']);
    $members = collect(['Ana Alpha', 'Bia Bravo', 'Caio Charlie', 'Duda Delta'])
        ->map(fn (string $name) => User::factory()->create(['name' => $name]))
        ->all();

    $group = Group::factory()->organizedBy($organizer, ...$members)->create();

    test()->actingAs($organizer)->post(route('groups.draw.store', $group))->assertRedirect();

    $pairs = Assignment::query()->where('group_id', $group->id)->pluck('receiver_id', 'giver_id')->all();

    return [$group->refresh(), $organizer, $members, $pairs];
}

/**
 * Inertia partial reload asking the server for specific props of the group page.
 */
function partialReload(Group $group, User $as, string $props, array $query = []): TestResponse
{
    return test()->actingAs($as)->get(route('groups.show', ['group' => $group, ...$query]), inertiaPartialHeaders($props));
}

/**
 * @return array<string, string>
 */
function inertiaPartialHeaders(string $props): array
{
    return [
        'X-Inertia' => 'true',
        // Without the current asset version Inertia answers 409 before the controller runs.
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'groups/show',
        'X-Inertia-Partial-Data' => $props,
    ];
}

function receiverName(int $userId): string
{
    return User::query()->findOrFail($userId)->name;
}

test('the group page never includes a match unless explicitly asked for', function () {
    [$group, $organizer, $members] = drawnGroup();

    foreach ([$organizer, ...$members] as $viewer) {
        $this->actingAs($viewer)
            ->get(route('groups.show', $group))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->missing('my_match')->where('can.reveal', true))
            ->assertDontSee('receiver', false)
            ->assertDontSee('giver', false)
            ->assertDontSee('assignments', false);
    }
});

test('every member, the organizer included, gets exactly their own match', function () {
    [$group, $organizer, $members, $pairs] = drawnGroup();

    foreach ([$organizer, ...$members] as $viewer) {
        partialReload($group, $viewer, 'my_match')
            ->assertOk()
            ->assertJsonPath('props.my_match.receiver_name', receiverName($pairs[$viewer->id]))
            ->assertJsonMissingPath('props.participants');
    }
});

test('no route reveals someone else\'s match', function () {
    [$group, $organizer, $members, $pairs] = drawnGroup();
    $viewer = $members[0];

    // What a leak would look like: "receiver_name":"<someone else's receiver>".
    $leaks = collect($pairs)
        ->except($viewer->id)
        ->map(fn (int $receiverId) => '"receiver_name":"'.receiverName($receiverId).'"')
        ->values();

    $groupRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true))
        ->filter(fn (RoutingRoute $route) => str_contains($route->uri(), '{group}'));

    expect($groupRoutes)->not->toBeEmpty();

    foreach ($groupRoutes as $route) {
        $url = url(str_replace('{group}', $group->ulid, $route->uri()));

        foreach ([
            $this->actingAs($viewer)->get($url),
            $this->actingAs($viewer)->get($url.'?user_id='.$organizer->id.'&giver_id='.$organizer->id),
            partialReload($group, $viewer, 'my_match,participants,assignments,exclusions'),
        ] as $response) {
            foreach ($leaks as $leak) {
                expect($response->getContent())->not->toContain($leak);
            }
        }
    }
});

test('query parameters cannot switch whose match is returned', function () {
    [$group, $organizer, $members, $pairs] = drawnGroup();
    $viewer = $members[1];

    partialReload($group, $viewer, 'my_match', ['user_id' => $organizer->id, 'giver_id' => $organizer->id])
        ->assertJsonPath('props.my_match.receiver_name', receiverName($pairs[$viewer->id]));
});

test('outsiders cannot see the group or ask for a match', function () {
    [$group] = drawnGroup();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get(route('groups.show', $group))->assertForbidden();
    partialReload($group, $outsider, 'my_match')->assertForbidden();
});

test('guests are sent to log in', function () {
    [$group] = drawnGroup();
    $this->app['auth']->forgetGuards();

    $this->get(route('groups.show', $group))->assertRedirect(route('login'));
    $this->get(route('groups.show', $group), inertiaPartialHeaders('my_match'))->assertRedirect(route('login'));
});

test('asking for a match before the draw is forbidden', function () {
    [$group, $organizer] = groupWithMembers(3);

    partialReload($group, $organizer, 'my_match')->assertForbidden();
});

test('a match belongs to one group only', function () {
    [$first, , [$ana]] = drawnGroup();
    [$second, $organizer2] = groupWithMembers(3);
    $second->participants()->create(['user_id' => $ana->id, 'role' => 'participant', 'joined_at' => now()]);

    // Ana is in the second group, which has not been drawn yet.
    partialReload($second, $ana, 'my_match')->assertForbidden();
    partialReload($first, $ana, 'my_match')->assertOk();
});

test('a gift rule reaches only the person who drew its owner', function () {
    [$group, $organizer, $members, $pairs] = drawnGroup();
    $owner = $members[2];
    $group->participantFor($owner)?->update(['gift_rule' => 'Only chocolate, please']);

    $giverId = array_search($owner->id, $pairs, true);

    foreach ([$organizer, ...$members] as $viewer) {
        $match = partialReload($group, $viewer, 'my_match')->json('props.my_match');

        if ($viewer->id === $giverId) {
            expect($match['receiver_gift_rule'])->toBe('Only chocolate, please');
        } else {
            expect($match['receiver_gift_rule'] ?? null)->not->toBe('Only chocolate, please');
        }
    }

    // Members who are neither the owner, the organizer nor the giver never see it on the page either.
    foreach ($members as $viewer) {
        if ($viewer->id === $owner->id || $viewer->id === $giverId) {
            continue;
        }

        $this->actingAs($viewer)->get(route('groups.show', $group))->assertDontSee('Only chocolate, please');
    }
});

test('models never serialize assignment details', function () {
    [$group] = drawnGroup();

    $assignment = Assignment::query()->first();

    expect($assignment?->toArray())->not->toHaveKeys(['giver_id', 'receiver_id'])
        ->and($group->load('participants')->toArray())->not->toHaveKey('assignments')
        ->and($group->toArray())->not->toHaveKeys(['id', 'invite_token']);
});
