<?php

namespace App\Http\Controllers;

use App\Actions\Draw\FindMyMatch;
use App\Actions\Groups\CreateGroup;
use App\Enums\Currency;
use App\Enums\Locale;
use App\Http\Requests\Groups\GroupRequest;
use App\Http\Resources\BudgetData;
use App\Http\Resources\GroupPage;
use App\Http\Resources\GroupSummaryResource;
use App\Models\Group;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    /**
     * The dashboard: every group the user belongs to, soonest event first.
     */
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        $memberships = GroupParticipant::query()
            ->where('user_id', $user->id)
            ->with(['group' => fn ($query) => $query->withCount('participants')])
            ->get()
            ->sortBy(fn (GroupParticipant $membership): string => $membership->group->event_date->toDateString())
            ->values();

        return Inertia::render('dashboard', [
            'groups' => $memberships->map(fn (GroupParticipant $membership): array => (new GroupSummaryResource($membership))->resolve($request))->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('groups/create', [
            'currencies' => Currency::values(),
            'defaultCurrency' => Currency::defaultFor(Locale::tryFrom(app()->getLocale()) ?? Locale::default())->value,
        ]);
    }

    public function store(GroupRequest $request, CreateGroup $createGroup, #[CurrentUser] User $user): RedirectResponse
    {
        $group = $createGroup->handle($user, $request->toAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.created')]);

        return to_route('groups.show', $group);
    }

    public function show(Group $group, FindMyMatch $findMyMatch, #[CurrentUser] User $user): Response
    {
        $group->load(['participants' => fn ($query) => $query->orderBy('joined_at')->orderBy('id'), 'participants.user:id,name']);

        Gate::authorize('view', $group);

        return Inertia::render('groups/show', [
            ...GroupPage::for($group, $user),
            // Never sent with the page: only when the viewer clicks "Reveal" (a partial reload asking for it).
            'my_match' => Inertia::optional(function () use ($group, $user, $findMyMatch): ?array {
                Gate::forUser($user)->authorize('reveal', $group);

                return $findMyMatch->handle($group, $user);
            }),
        ]);
    }

    public function edit(Group $group): Response
    {
        Gate::authorize('update', $group);

        return Inertia::render('groups/edit', [
            'group' => [
                'id' => $group->ulid,
                'name' => $group->name,
                'description' => $group->description,
                'budget' => BudgetData::from($group),
                'event_date' => $group->event_date->toDateString(),
            ],
            'currencies' => Currency::values(),
            'defaultCurrency' => $group->budget_currency->value ?? Currency::defaultFor(Locale::tryFrom(app()->getLocale()) ?? Locale::default())->value,
        ]);
    }

    public function update(GroupRequest $request, Group $group): RedirectResponse
    {
        Gate::authorize('update', $group);

        $group->update($request->toAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.updated')]);

        return to_route('groups.show', $group);
    }

    public function destroy(Group $group): RedirectResponse
    {
        Gate::authorize('delete', $group);

        $group->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.deleted')]);

        return to_route('dashboard');
    }
}
