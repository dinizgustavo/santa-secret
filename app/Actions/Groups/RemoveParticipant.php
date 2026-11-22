<?php

namespace App\Actions\Groups;

use App\Exceptions\GroupRuleViolation;
use App\Models\Group;
use App\Models\GroupParticipant;
use Illuminate\Support\Facades\DB;

/**
 * Removes someone from a group, either by the organizer or by leaving.
 * Their exclusions go with them (composite foreign keys cascade).
 */
final class RemoveParticipant
{
    /**
     * @throws GroupRuleViolation when the group was drawn or the participant is the organizer
     */
    public function handle(Group $group, GroupParticipant $participant): void
    {
        DB::transaction(function () use ($group, $participant): void {
            $locked = Group::query()->lockForUpdate()->findOrFail($group->id);

            if ($locked->isDrawn()) {
                throw GroupRuleViolation::because('groups.errors.locked');
            }

            if ($participant->isOrganizer()) {
                throw GroupRuleViolation::because('groups.errors.organizer_cannot_leave');
            }

            $locked->participants()->whereKey($participant->id)->delete();
        });
    }
}
