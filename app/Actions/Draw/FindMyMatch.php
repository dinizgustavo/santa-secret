<?php

namespace App\Actions\Draw;

use App\Models\Assignment;
use App\Models\Group;
use App\Models\GroupParticipant;
use App\Models\User;

/**
 * The only way an assignment ever leaves the database: the viewer's own row,
 * looked up by the authenticated user. No id from the request is involved,
 * so there is nothing to tamper with.
 */
final class FindMyMatch
{
    /**
     * @return array{receiver_name: string, receiver_gift_rule: string|null}|null
     */
    public function handle(Group $group, User $viewer): ?array
    {
        $assignment = Assignment::query()
            ->where('group_id', $group->id)
            ->where('giver_id', $viewer->id)
            ->with('receiver:id,name')
            ->first();

        if ($assignment === null) {
            return null;
        }

        $receiverRule = GroupParticipant::query()
            ->where('group_id', $group->id)
            ->where('user_id', $assignment->receiver_id)
            ->value('gift_rule');

        return [
            'receiver_name' => $assignment->receiver->name,
            'receiver_gift_rule' => is_string($receiverRule) ? $receiverRule : null,
        ];
    }
}
