<?php

namespace App\Actions\Auth;

use App\Enums\ParticipantRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a user account. Groups they organize go with it, because a group without
 * an organizer can never be drawn or managed again. (Accounts in a group that was
 * already drawn cannot be deleted: ProfileDeleteRequest stops that earlier.)
 */
final class DeleteAccount
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            Group::query()
                ->whereHas('participants', fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('role', ParticipantRole::Organizer))
                ->delete();

            $user->delete();
        });
    }
}
