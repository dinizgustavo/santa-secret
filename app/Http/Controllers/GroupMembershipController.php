<?php

namespace App\Http\Controllers;

use App\Actions\Groups\RemoveParticipant;
use App\Models\Group;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Membership changes before the draw: regenerating the invite link,
 * the organizer removing someone, and members leaving on their own.
 */
class GroupMembershipController extends Controller
{
    public function regenerateInvite(Group $group): RedirectResponse
    {
        Gate::authorize('manageInvite', $group);

        $group->forceFill(['invite_token' => Group::newInviteToken()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.invite_regenerated')]);

        return back();
    }

    public function remove(Group $group, GroupParticipant $participant, RemoveParticipant $removeParticipant): RedirectResponse
    {
        Gate::authorize('removeParticipant', [$group, $participant]);

        $removeParticipant->handle($group, $participant);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.participant_removed')]);

        return back();
    }

    public function leave(Group $group, RemoveParticipant $removeParticipant, #[CurrentUser] User $user): RedirectResponse
    {
        Gate::authorize('leave', $group);

        $participant = $group->participantFor($user);
        abort_if($participant === null, 403);

        $removeParticipant->handle($group, $participant);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.left', ['group' => $group->name])]);

        return to_route('dashboard');
    }
}
