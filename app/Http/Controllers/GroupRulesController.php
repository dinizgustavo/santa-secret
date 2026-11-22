<?php

namespace App\Http\Controllers;

use App\Actions\Groups\AddExclusion;
use App\Exceptions\GroupRuleViolation;
use App\Http\Requests\Groups\StoreExclusionRequest;
use App\Http\Requests\Groups\UpdateGiftRuleRequest;
use App\Models\Group;
use App\Models\GroupExclusion;
use App\Models\GroupParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The organizer's tools to shape the draw: exclusions (pairs who must not draw each other)
 * and per-person gift rules.
 */
class GroupRulesController extends Controller
{
    public function storeExclusion(StoreExclusionRequest $request, Group $group, AddExclusion $addExclusion): RedirectResponse
    {
        Gate::authorize('manageExclusions', $group);

        [$first, $second] = $request->participants();
        $addExclusion->handle($group, $first, $second);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.exclusion_added')]);

        return back();
    }

    public function destroyExclusion(Group $group, GroupExclusion $exclusion): RedirectResponse
    {
        Gate::authorize('manageExclusions', $group);

        if ($group->isDrawn()) {
            throw GroupRuleViolation::because('groups.errors.locked');
        }

        $exclusion->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.exclusion_removed')]);

        return back();
    }

    public function updateGiftRule(UpdateGiftRuleRequest $request, Group $group, GroupParticipant $participant): RedirectResponse
    {
        Gate::authorize('manageGiftRules', $group);

        $participant->update(['gift_rule' => $request->giftRule()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.gift_rule_saved')]);

        return back();
    }
}
