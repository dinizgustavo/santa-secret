<?php

namespace App\Actions\Draw;

use App\Models\Group;
use Illuminate\Support\Facades\DB;

/**
 * Deletes every assignment and unlocks the group.
 */
final class ResetDraw
{
    public function handle(Group $group): void
    {
        DB::transaction(function () use ($group): void {
            $locked = Group::query()->lockForUpdate()->findOrFail($group->id);

            $locked->assignments()->delete();
            $locked->forceFill(['drawn_at' => null])->save();
        });
    }
}
