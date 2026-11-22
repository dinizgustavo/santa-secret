<?php

namespace Database\Factories;

use App\Enums\ParticipantRole;
use App\Models\Group;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupParticipant>
 */
class GroupParticipantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'role' => ParticipantRole::Participant,
            'gift_rule' => null,
            'joined_at' => now(),
        ];
    }
}
