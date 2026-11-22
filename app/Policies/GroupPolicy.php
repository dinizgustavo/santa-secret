<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\GroupParticipant;
use App\Models\User;

/**
 * Who may do what with a group. State rules that can change between the check and the write
 * (e.g. "not drawn yet") are enforced again inside the actions, under a row lock.
 */
class GroupPolicy
{
    public function view(User $user, Group $group): bool
    {
        return $this->isMember($user, $group);
    }

    public function update(User $user, Group $group): bool
    {
        return $this->isOrganizer($user, $group);
    }

    public function delete(User $user, Group $group): bool
    {
        return $this->isOrganizer($user, $group);
    }

    public function manageInvite(User $user, Group $group): bool
    {
        return $this->isOrganizer($user, $group) && ! $group->isDrawn();
    }

    public function removeParticipant(User $user, Group $group, GroupParticipant $participant): bool
    {
        return $this->isOrganizer($user, $group)
            && ! $group->isDrawn()
            && $participant->group_id === $group->id
            && ! $participant->isOrganizer();
    }

    public function leave(User $user, Group $group): bool
    {
        $participant = $group->participantFor($user);

        return $participant !== null && ! $participant->isOrganizer() && ! $group->isDrawn();
    }

    public function manageExclusions(User $user, Group $group): bool
    {
        return $this->isOrganizer($user, $group) && ! $group->isDrawn();
    }

    public function manageGiftRules(User $user, Group $group): bool
    {
        return $this->isOrganizer($user, $group);
    }

    public function draw(User $user, Group $group): bool
    {
        return $this->isOrganizer($user, $group) && ! $group->isDrawn();
    }

    public function resetDraw(User $user, Group $group): bool
    {
        return $this->isOrganizer($user, $group) && $group->isDrawn();
    }

    /**
     * Every member may see their own match, organizer included. Nobody may see anyone else's.
     */
    public function reveal(User $user, Group $group): bool
    {
        return $this->isMember($user, $group) && $group->isDrawn();
    }

    private function isMember(User $user, Group $group): bool
    {
        return $group->participantFor($user) !== null;
    }

    private function isOrganizer(User $user, Group $group): bool
    {
        return $group->participantFor($user)?->isOrganizer() ?? false;
    }
}
