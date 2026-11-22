<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Enums\ParticipantRole;
use App\Models\Group;
use App\Models\GroupExclusion;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local demo data: `php artisan migrate:fresh --seed`.
 *
 * Every demo account uses the password "password". Log in as organizer@example.com
 * to run the draw, then as any of the others to reveal their match.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $organizer = User::factory()->create(['name' => 'Olga Organizer', 'email' => 'organizer@example.com']);

        $ana = User::factory()->create(['name' => 'Ana Souza', 'email' => 'ana@example.com']);
        $bruno = User::factory()->create(['name' => 'Bruno Lima', 'email' => 'bruno@example.com']);
        $carla = User::factory()->create(['name' => 'Carla Dias', 'email' => 'carla@example.com']);
        $friends = [
            $ana,
            $bruno,
            $carla,
            User::factory()->create(['name' => 'Diego Rocha', 'email' => 'diego@example.com']),
            User::factory()->create(['name' => 'Elisa Costa', 'email' => 'elisa@example.com']),
        ];

        $group = Group::factory()
            ->withBudget(5000, Currency::BRL)
            ->create([
                'name' => 'Natal da família',
                'description' => 'Troca de presentes na casa da vó, depois do almoço.',
                'event_date' => now()->addMonth()->toDateString(),
            ]);

        $group->participants()->create(['user_id' => $organizer->id, 'role' => ParticipantRole::Organizer, 'joined_at' => now()]);

        foreach ($friends as $friend) {
            $group->participants()->create(['user_id' => $friend->id, 'role' => ParticipantRole::Participant, 'joined_at' => now()]);
        }

        // Ana is not comfortable with gifts: whoever draws her gives chocolate, and she gives chocolate too.
        $group->participants()->where('user_id', $ana->id)->update(['gift_rule' => 'Prefere chocolate em vez de presente.']);

        // Bruno and Carla are a couple.
        $group->exclusions()->create(GroupExclusion::orderedPair($bruno->id, $carla->id));
    }
}
