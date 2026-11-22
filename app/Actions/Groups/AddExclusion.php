<?php

namespace App\Actions\Groups;

use App\Exceptions\GroupRuleViolation;
use App\Models\Group;
use App\Models\GroupExclusion;
use App\Models\GroupParticipant;
use Illuminate\Support\Facades\DB;

final class AddExclusion
{
    /**
     * @throws GroupRuleViolation when the group was drawn
     */
    public function handle(Group $group, GroupParticipant $first, GroupParticipant $second): GroupExclusion
    {
        return DB::transaction(function () use ($group, $first, $second): GroupExclusion {
            $locked = Group::query()->lockForUpdate()->findOrFail($group->id);

            if ($locked->isDrawn()) {
                throw GroupRuleViolation::because('groups.errors.locked');
            }

            return $locked->exclusions()->firstOrCreate(
                GroupExclusion::orderedPair($first->user_id, $second->user_id),
            );
        });
    }
}
