<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\ParticipantRole;
use App\Models\Group;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Amigo secreto '.fake()->unique()->word(),
            'description' => null,
            'budget_max_amount' => null,
            'budget_currency' => null,
            'event_date' => now()->addMonth()->toDateString(),
        ];
    }

    public function withBudget(int $amountInCents = 5000, Currency $currency = Currency::BRL): static
    {
        return $this->state(fn () => [
            'budget_max_amount' => $amountInCents,
            'budget_currency' => $currency,
        ]);
    }

    /**
     * Creates the group with this user as organizer and the given users as participants.
     */
    public function organizedBy(User $organizer, User ...$participants): static
    {
        return $this->afterCreating(function (Group $group) use ($organizer, $participants): void {
            GroupParticipant::factory()->for($group)->for($organizer)->create(['role' => ParticipantRole::Organizer]);

            foreach ($participants as $participant) {
                GroupParticipant::factory()->for($group)->for($participant)->create();
            }
        });
    }
}
