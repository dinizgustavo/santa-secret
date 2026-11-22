<?php

namespace App\Actions\Draw;

use App\Draw\DrawSolver;
use App\Draw\ImpossibleDraw;
use App\Exceptions\GroupRuleViolation;
use App\Models\Group;
use App\Models\GroupExclusion;
use Illuminate\Support\Facades\DB;

/**
 * Runs the draw once, server-side, inside a transaction: either every assignment
 * is saved and the group is locked, or nothing changes at all.
 */
final class RunDraw
{
    public function __construct(private readonly DrawSolver $solver) {}

    /**
     * @throws GroupRuleViolation when already drawn, too small, or impossible with the exclusions
     */
    public function handle(Group $group): void
    {
        DB::transaction(function () use ($group): void {
            // The row lock serializes this with joins, removals and a second click on "draw".
            $locked = Group::query()->lockForUpdate()->findOrFail($group->id);

            if ($locked->isDrawn()) {
                throw GroupRuleViolation::because('groups.errors.locked');
            }

            /** @var list<int> $people */
            $people = $locked->participants()->orderBy('id')->pluck('user_id')->all();

            if (count($people) < Group::MIN_PARTICIPANTS_TO_DRAW) {
                throw new GroupRuleViolation(__('groups.errors.not_enough_participants', ['min' => Group::MIN_PARTICIPANTS_TO_DRAW]));
            }

            $exclusions = array_values($locked->exclusions()
                ->get(['user_a_id', 'user_b_id'])
                ->map(fn (GroupExclusion $exclusion): array => [$exclusion->user_a_id, $exclusion->user_b_id])
                ->all());

            try {
                $assignment = $this->solver->solve($people, $exclusions);
            } catch (ImpossibleDraw) {
                throw GroupRuleViolation::because('groups.errors.impossible_draw');
            }

            $now = now();

            $locked->assignments()->insert(array_map(
                fn (int $giver, int $receiver): array => [
                    'group_id' => $locked->id,
                    'giver_id' => $giver,
                    'receiver_id' => $receiver,
                    'created_at' => $now,
                ],
                array_keys($assignment),
                array_values($assignment),
            ));

            $locked->forceFill(['drawn_at' => $now])->save();
        });
    }
}
