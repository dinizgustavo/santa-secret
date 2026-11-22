<?php

namespace App\Actions\Groups;

use App\Enums\ParticipantRole;
use App\Exceptions\GroupRuleViolation;
use App\Models\Group;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class JoinGroup
{
    /**
     * @throws GroupRuleViolation when the group was drawn or is full
     */
    public function handle(Group $group, User $user): GroupParticipant
    {
        return DB::transaction(function () use ($group, $user): GroupParticipant {
            // Serializes joins with the draw, so nobody slips in while it runs.
            $locked = Group::query()->lockForUpdate()->findOrFail($group->id);

            $existing = $locked->participants()->where('user_id', $user->id)->first();

            if ($existing !== null) {
                return $existing;
            }

            if ($locked->isDrawn()) {
                throw GroupRuleViolation::because('groups.errors.locked');
            }

            if ($locked->participants()->count() >= Group::MAX_PARTICIPANTS) {
                throw GroupRuleViolation::because('groups.errors.full');
            }

            return $locked->participants()->create([
                'user_id' => $user->id,
                'role' => ParticipantRole::Participant,
                'joined_at' => now(),
            ]);
        });
    }
}
