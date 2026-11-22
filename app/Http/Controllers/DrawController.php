<?php

namespace App\Http\Controllers;

use App\Actions\Draw\ResetDraw;
use App\Actions\Draw\RunDraw;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class DrawController extends Controller
{
    public function store(Group $group, RunDraw $runDraw): RedirectResponse
    {
        Gate::authorize('draw', $group);

        $runDraw->handle($group);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.drawn')]);

        return to_route('groups.show', $group);
    }

    public function destroy(Group $group, ResetDraw $resetDraw): RedirectResponse
    {
        Gate::authorize('resetDraw', $group);

        $resetDraw->handle($group);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('groups.flash.draw_reset')]);

        return to_route('groups.show', $group);
    }
}
