<?php

namespace App\Http\Controllers;

use App\Actions\Groups\JoinGroup;
use App\Http\Resources\BudgetData;
use App\Models\Group;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The shareable invite link. Opening it (GET) only shows the group; joining is a POST,
 * because chat apps such as WhatsApp fetch links to build previews.
 */
class InvitationController extends Controller
{
    public function show(string $token, #[CurrentUser] User $user): Response|RedirectResponse
    {
        $group = $this->groupFor($token);

        if ($group->participantFor($user) !== null) {
            return to_route('groups.show', $group);
        }

        $group->loadCount('participants')->load('organizerParticipant.user:id,name');

        return Inertia::render('invites/show', [
            'token' => $token,
            'group' => [
                'name' => $group->name,
                'description' => $group->description,
                'event_date' => $group->event_date->toDateString(),
                'budget' => BudgetData::from($group),
                'organizer_name' => $group->organizerParticipant?->user->name,
                'participants_count' => $group->participants_count ?? 0,
                'is_drawn' => $group->isDrawn(),
            ],
        ]);
    }

    public function store(string $token, JoinGroup $joinGroup, #[CurrentUser] User $user): RedirectResponse
    {
        $group = $this->groupFor($token);

        $joinGroup->handle($group, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.joined', ['group' => $group->name])]);

        return to_route('groups.show', $group);
    }

    private function groupFor(string $token): Group
    {
        return Group::query()->where('invite_token', $token)->firstOrFail();
    }
}
