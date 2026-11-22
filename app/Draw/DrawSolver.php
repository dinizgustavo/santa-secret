<?php

namespace App\Draw;

use InvalidArgumentException;
use Random\Randomizer;

/**
 * Finds a Secret Santa assignment: a permutation of the participants where
 * nobody gets themselves and no excluded pair (in either direction) is matched.
 *
 * Strategy:
 *  1. Randomized backtracking that looks for a single cycle (A -> B -> C -> ... -> A),
 *     which is the nicest outcome: nobody is in a closed pair of two, and the chain
 *     covers the whole group. Hamiltonian cycles are NP-hard in general, so this
 *     search has a step budget.
 *  2. If no single cycle is found within the budget (or none exists), a randomized
 *     bipartite matching (Kuhn's augmenting paths) finds any valid assignment.
 *     That search is exact, so when it fails the draw is provably impossible.
 *
 * All randomness comes from the injected Randomizer: production uses the default
 * cryptographically secure engine, tests use a seeded engine for reproducible results.
 *
 * Framework-free on purpose: plain integers in, plain integers out.
 */
final class DrawSolver
{
    public function __construct(
        private readonly Randomizer $randomizer = new Randomizer,
        private readonly int $cycleSearchBudget = 50_000,
    ) {}

    /**
     * @param  list<int>  $people  participant ids
     * @param  list<array{0: int, 1: int}>  $exclusions  pairs that must not draw each other
     * @return array<int, int> giver id => receiver id
     *
     * @throws ImpossibleDraw when no valid assignment exists
     */
    public function solve(array $people, array $exclusions = []): array
    {
        if (count($people) !== count(array_unique($people))) {
            throw new InvalidArgumentException('Participant ids must be unique.');
        }

        if (count($people) < 2) {
            throw new ImpossibleDraw('A draw needs at least two people.');
        }

        $allowed = $this->allowedReceivers($people, $exclusions);

        $assignment = $this->singleCycle($people, $allowed) ?? $this->anyAssignment($people, $allowed);

        $this->assertValid($assignment, $people, $allowed);

        return $assignment;
    }

    /**
     * @param  list<int>  $people
     * @param  list<array{0: int, 1: int}>  $exclusions
     * @return array<int, array<int, true>> giver => set of receivers they may get
     */
    private function allowedReceivers(array $people, array $exclusions): array
    {
        $forbidden = [];

        foreach ($exclusions as [$a, $b]) {
            $forbidden[$a][$b] = true;
            $forbidden[$b][$a] = true;
        }

        $allowed = [];

        foreach ($people as $giver) {
            $allowed[$giver] = [];

            foreach ($people as $receiver) {
                if ($giver !== $receiver && ! isset($forbidden[$giver][$receiver])) {
                    $allowed[$giver][$receiver] = true;
                }
            }

            if ($allowed[$giver] === []) {
                throw new ImpossibleDraw("Participant {$giver} has nobody they are allowed to draw.");
            }
        }

        return $allowed;
    }

    /**
     * Randomized depth-first search for a Hamiltonian cycle, within a step budget.
     *
     * @param  list<int>  $people
     * @param  array<int, array<int, true>>  $allowed
     * @return array<int, int>|null
     */
    private function singleCycle(array $people, array $allowed): ?array
    {
        /** @var list<int> $order */
        $order = $this->randomizer->shuffleArray($people);
        $start = $order[0];

        $path = [$start];
        $visited = [$start => true];
        $steps = 0;

        if (! $this->extendCycle($start, $start, $allowed, $path, $visited, $steps)) {
            return null;
        }

        $total = count($path);
        $assignment = [];

        foreach ($path as $index => $giver) {
            $assignment[$giver] = $path[($index + 1) % $total];
        }

        return $assignment;
    }

    /**
     * @param  array<int, array<int, true>>  $allowed
     * @param  list<int>  $path  people chained so far, in order
     * @param  array<int, true>  $visited
     */
    private function extendCycle(int $current, int $start, array $allowed, array &$path, array &$visited, int &$steps): bool
    {
        if (++$steps > $this->cycleSearchBudget) {
            return false;
        }

        if (count($path) === count($allowed)) {
            // Everyone is on the chain: it only counts if the last person may give to the first.
            return isset($allowed[$current][$start]);
        }

        /** @var list<int> $candidates */
        $candidates = $this->randomizer->shuffleArray(array_keys($allowed[$current]));

        foreach ($candidates as $next) {
            if (isset($visited[$next])) {
                continue;
            }

            $path[] = $next;
            $visited[$next] = true;

            if ($this->extendCycle($next, $start, $allowed, $path, $visited, $steps)) {
                return true;
            }

            array_pop($path);
            unset($visited[$next]);
        }

        return false;
    }

    /**
     * Randomized maximum bipartite matching (givers -> receivers) with Kuhn's algorithm.
     *
     * @param  list<int>  $people
     * @param  array<int, array<int, true>>  $allowed
     * @return array<int, int>
     *
     * @throws ImpossibleDraw
     */
    private function anyAssignment(array $people, array $allowed): array
    {
        /** @var array<int, list<int>> $options */
        $options = [];

        foreach ($people as $giver) {
            $options[$giver] = $this->randomizer->shuffleArray(array_keys($allowed[$giver]));
        }

        /** @var array<int, int> $giverOf receiver => giver */
        $giverOf = [];

        /** @var list<int> $givers */
        $givers = $this->randomizer->shuffleArray($people);

        foreach ($givers as $giver) {
            $seen = [];

            if (! $this->augment($giver, $options, $giverOf, $seen)) {
                throw new ImpossibleDraw('The exclusions leave no valid assignment.');
            }
        }

        return array_flip($giverOf);
    }

    /**
     * Tries to give $giver a receiver, re-routing earlier givers along an augmenting path if needed.
     *
     * @param  array<int, list<int>>  $options
     * @param  array<int, int>  $giverOf  receiver => giver
     * @param  array<int, true>  $seen  receivers already tried in this search
     */
    private function augment(int $giver, array $options, array &$giverOf, array &$seen): bool
    {
        foreach ($options[$giver] as $receiver) {
            if (isset($seen[$receiver])) {
                continue;
            }

            $seen[$receiver] = true;

            if (! isset($giverOf[$receiver]) || $this->augment($giverOf[$receiver], $options, $giverOf, $seen)) {
                $giverOf[$receiver] = $giver;

                return true;
            }
        }

        return false;
    }

    /**
     * Last line of defence before anything is saved.
     *
     * @param  array<int, int>  $assignment
     * @param  list<int>  $people
     * @param  array<int, array<int, true>>  $allowed
     */
    private function assertValid(array $assignment, array $people, array $allowed): void
    {
        $givers = array_keys($assignment);
        $receivers = array_values($assignment);

        sort($givers);
        sort($receivers);
        $expected = $people;
        sort($expected);

        if ($givers !== $expected || $receivers !== $expected) {
            throw new \LogicException('Draw is not a permutation of the participants.');
        }

        foreach ($assignment as $giver => $receiver) {
            if (! isset($allowed[$giver][$receiver])) {
                throw new \LogicException("Draw broke a rule: {$giver} -> {$receiver}.");
            }
        }
    }
}
