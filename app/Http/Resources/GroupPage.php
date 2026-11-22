<?php

namespace App\Http\Resources;

use App\Models\Group;
use App\Models\GroupExclusion;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Builds the props for the group page for one specific viewer.
 *
 * Visibility rules live here, in one place:
 * - everybody in the group sees names, roles and the group details;
 * - gift rules are visible to the organizer and to the person they belong to
 *   (the giver sees the receiver's rule only through their own match);
 * - the invite link and the exclusions are organizer-only;
 * - assignments are never part of this payload; a member's own match is loaded
 *   on demand by the reveal endpoint.
 */
final class GroupPage
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Group $group, User $viewer): array
    {
        $group->loadMissing(['participants' => fn ($query) => $query->orderBy('joined_at')->orderBy('id'), 'participants.user:id,name']);

        $me = $group->participantFor($viewer);
        $isOrganizer = $me?->isOrganizer() ?? false;

        return [
            'group' => [
                'id' => $group->ulid,
                'name' => $group->name,
                'description' => $group->description,
                'budget' => BudgetData::from($group),
                'event_date' => $group->event_date->toDateString(),
                'is_drawn' => $group->isDrawn(),
                'drawn_at' => $group->drawn_at?->toIso8601String(),
                'min_participants_to_draw' => Group::MIN_PARTICIPANTS_TO_DRAW,
            ],
            'me' => [
                'participant_id' => $me?->id,
                'is_organizer' => $isOrganizer,
                'gift_rule' => $me?->gift_rule,
            ],
            'participants' => $group->participants
                ->map(fn (GroupParticipant $participant): array => [
                    'id' => $participant->id,
                    'name' => $participant->user->name,
                    'is_organizer' => $participant->isOrganizer(),
                    'is_me' => $participant->user_id === $viewer->id,
                    'joined_at' => $participant->joined_at->toIso8601String(),
                    ...($isOrganizer || $participant->user_id === $viewer->id
                        ? ['gift_rule' => $participant->gift_rule]
                        : []),
                ])
                ->values()
                ->all(),
            'invite_url' => Gate::forUser($viewer)->allows('manageInvite', $group)
                ? route('invites.show', $group->invite_token)
                : null,
            'exclusions' => $isOrganizer ? self::exclusions($group) : [],
            'can' => [
                'update' => Gate::forUser($viewer)->allows('update', $group),
                'delete' => Gate::forUser($viewer)->allows('delete', $group),
                'manage_invite' => Gate::forUser($viewer)->allows('manageInvite', $group),
                'manage_participants' => $isOrganizer && ! $group->isDrawn(),
                'manage_exclusions' => Gate::forUser($viewer)->allows('manageExclusions', $group),
                'manage_gift_rules' => Gate::forUser($viewer)->allows('manageGiftRules', $group),
                'leave' => Gate::forUser($viewer)->allows('leave', $group),
                'draw' => Gate::forUser($viewer)->allows('draw', $group),
                'reset_draw' => Gate::forUser($viewer)->allows('resetDraw', $group),
                'reveal' => Gate::forUser($viewer)->allows('reveal', $group),
            ],
        ];
    }

    /**
     * @return list<array{id: int, a: array{participant_id: int, name: string}, b: array{participant_id: int, name: string}}>
     */
    private static function exclusions(Group $group): array
    {
        $byUser = $group->participants->keyBy('user_id');

        return array_values($group->exclusions()
            ->orderBy('id')
            ->get()
            ->map(function (GroupExclusion $exclusion) use ($byUser): ?array {
                $a = $byUser->get($exclusion->user_a_id);
                $b = $byUser->get($exclusion->user_b_id);

                if (! $a instanceof GroupParticipant || ! $b instanceof GroupParticipant) {
                    return null;
                }

                return [
                    'id' => $exclusion->id,
                    'a' => ['participant_id' => $a->id, 'name' => $a->user->name],
                    'b' => ['participant_id' => $b->id, 'name' => $b->user->name],
                ];
            })
            ->filter()
            ->all());
    }
}
