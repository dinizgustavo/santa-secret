<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests run against PostgreSQL (see phpunit.xml) because the schema
| relies on Postgres-only features such as partial unique indexes and
| composite foreign keys.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * A group with an organizer and $participants other members.
 *
 * @return array{0: Group, 1: User, 2: list<User>}
 */
function groupWithMembers(int $participants = 2): array
{
    $organizer = User::factory()->create(['name' => 'Organizer']);
    $members = array_values(User::factory()->count($participants)->create()->all());

    $group = Group::factory()->organizedBy($organizer, ...$members)->create();

    return [$group->refresh(), $organizer, $members];
}
