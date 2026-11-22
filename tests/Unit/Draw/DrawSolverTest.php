<?php

use App\Draw\DrawSolver;
use App\Draw\ImpossibleDraw;
use Random\Engine\Mt19937;
use Random\Randomizer;

function seededSolver(int $seed = 42, int $budget = 50_000): DrawSolver
{
    return new DrawSolver(new Randomizer(new Mt19937($seed)), $budget);
}

/**
 * Follows giver -> receiver links from the first giver and returns the length of that cycle.
 *
 * @param  array<int, int>  $assignment
 */
function cycleLength(array $assignment): int
{
    $start = array_key_first($assignment);
    $current = $assignment[$start];
    $length = 1;

    while ($current !== $start) {
        $current = $assignment[$current];
        $length++;
    }

    return $length;
}

/**
 * @param  array<int, int>  $assignment
 * @param  list<int>  $people
 */
function expectValidDraw(array $assignment, array $people): void
{
    $givers = array_keys($assignment);
    $receivers = array_values($assignment);
    sort($givers);
    sort($receivers);
    sort($people);

    expect($givers)->toBe($people)
        ->and($receivers)->toBe($people);

    foreach ($assignment as $giver => $receiver) {
        expect($receiver)->not->toBe($giver);
    }
}

test('it produces a valid permutation with no self-draws', function (int $size, int $seed) {
    $people = range(1, $size);

    expectValidDraw(seededSolver($seed)->solve($people), $people);
})->with([2, 3, 4, 7, 15, 50])->with([1, 2, 3, 99]);

test('it prefers a single cycle when nothing prevents it', function (int $seed) {
    $people = range(10, 29);

    $assignment = seededSolver($seed)->solve($people);

    expect(cycleLength($assignment))->toBe(count($people));
})->with(range(1, 10));

test('exclusions are respected in both directions', function (int $seed) {
    $people = range(1, 8);
    $couples = [[1, 2], [3, 4], [5, 6], [7, 8]];

    $assignment = seededSolver($seed)->solve($people, $couples);

    expectValidDraw($assignment, $people);
    foreach ($couples as [$a, $b]) {
        expect($assignment[$a])->not->toBe($b)
            ->and($assignment[$b])->not->toBe($a);
    }
    expect(cycleLength($assignment))->toBe(8);
})->with(range(1, 25));

test('it falls back to several cycles when a single one is impossible', function () {
    // Only 1 <-> 2 and 3 <-> 4 are allowed, so the only valid draw is two pairs.
    $exclusions = [[1, 3], [1, 4], [2, 3], [2, 4]];

    $assignment = seededSolver()->solve([1, 2, 3, 4], $exclusions);

    expect($assignment)->toEqualCanonicalizing([1 => 2, 2 => 1, 3 => 4, 4 => 3]);
});

test('it reports an impossible draw when someone has nobody to draw', function () {
    seededSolver()->solve([1, 2, 3], [[1, 2], [1, 3]]);
})->throws(ImpossibleDraw::class);

test('it reports an impossible draw that only the matching can prove', function () {
    // 1, 2 and 3 may each only give to 4, but 4 can receive only once.
    seededSolver()->solve([1, 2, 3, 4], [[1, 2], [1, 3], [2, 3]]);
})->throws(ImpossibleDraw::class);

test('two people can only swap gifts', function () {
    $assignment = seededSolver()->solve([7, 9]);
    ksort($assignment);

    expect($assignment)->toBe([7 => 9, 9 => 7]);
});

test('it needs at least two people', function () {
    seededSolver()->solve([1]);
})->throws(ImpossibleDraw::class);

test('participant ids must be unique', function () {
    seededSolver()->solve([1, 2, 2]);
})->throws(InvalidArgumentException::class);

test('the same seed always gives the same draw', function () {
    $people = range(1, 12);
    $exclusions = [[1, 2], [5, 9]];

    expect(seededSolver(2026)->solve($people, $exclusions))
        ->toBe(seededSolver(2026)->solve($people, $exclusions));
});

test('different seeds give different draws', function () {
    $people = range(1, 12);

    $draws = array_map(fn (int $seed) => seededSolver($seed)->solve($people), range(1, 10));

    expect(count(array_unique(array_map('serialize', $draws))))->toBeGreaterThan(1);
});

test('it still finds a valid draw when the cycle search runs out of budget', function () {
    $people = range(1, 30);
    $exclusions = [[1, 2], [3, 4], [5, 6]];

    $assignment = seededSolver(7, budget: 1)->solve($people, $exclusions);

    expectValidDraw($assignment, $people);
});

test('a large group with many exclusions is solved quickly', function () {
    $people = range(1, 100);
    $exclusions = [];
    for ($i = 1; $i < 100; $i += 2) {
        $exclusions[] = [$i, $i + 1];
    }

    $started = hrtime(true);
    $assignment = seededSolver(3)->solve($people, $exclusions);
    $elapsedMs = (hrtime(true) - $started) / 1_000_000;

    expectValidDraw($assignment, $people);
    expect($elapsedMs)->toBeLessThan(1000);
});
