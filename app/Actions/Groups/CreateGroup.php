<?php

namespace App\Actions\Groups;

use App\Enums\ParticipantRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateGroup
{
    /**
     * @param  array{name: string, description: string|null, budget_max_amount: int|null, budget_currency: string|null, event_date: string}  $attributes
     */
    public function handle(User $organizer, array $attributes): Group
    {
        return DB::transaction(function () use ($organizer, $attributes): Group {
            $group = Group::query()->create($attributes);

            $group->participants()->create([
                'user_id' => $organizer->id,
                'role' => ParticipantRole::Organizer,
                'joined_at' => now(),
            ]);

            return $group;
        });
    }
}
