<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            // Public identifier used in URLs, so group ids cannot be guessed or enumerated.
            $table->ulid('ulid')->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            // Spending limit in minor units (cents) plus ISO 4217 currency, e.g. 5000 + BRL = "up to R$ 50,00".
            $table->unsignedInteger('budget_max_amount')->nullable();
            $table->char('budget_currency', 3)->nullable();
            $table->date('event_date');
            $table->string('invite_token', 64)->unique();
            // Set when the draw runs; while set, the group is locked (no joining, leaving or removing).
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();
        });

        Schema::create('group_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            // Organizer-defined rule for this person in this group, e.g. "prefers chocolate".
            // Only the organizer, the person and whoever draws them may ever see it.
            $table->string('gift_rule', 200)->nullable();
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('group_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            // Stored once per pair with user_a_id < user_b_id, which makes the rule bidirectional by construction.
            $table->unsignedBigInteger('user_a_id');
            $table->unsignedBigInteger('user_b_id');
            $table->timestamp('created_at')->nullable();

            $table->unique(['group_id', 'user_a_id', 'user_b_id']);

            // Both people must belong to the group; removing a participant removes their exclusions.
            $table->foreign(['group_id', 'user_a_id'])->references(['group_id', 'user_id'])->on('group_participants')->cascadeOnDelete();
            $table->foreign(['group_id', 'user_b_id'])->references(['group_id', 'user_id'])->on('group_participants')->cascadeOnDelete();
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('giver_id');
            $table->unsignedBigInteger('receiver_id');
            $table->timestamp('created_at')->nullable();

            // Everyone gives exactly once and receives exactly once.
            $table->unique(['group_id', 'giver_id']);
            $table->unique(['group_id', 'receiver_id']);
            $table->index('receiver_id');

            // NO ACTION (checked at the end of the statement): deleting the whole group still cascades,
            // but a participant (or their user account) cannot disappear from a group that was drawn.
            $table->foreign(['group_id', 'giver_id'])->references(['group_id', 'user_id'])->on('group_participants')->noActionOnDelete();
            $table->foreign(['group_id', 'receiver_id'])->references(['group_id', 'user_id'])->on('group_participants')->noActionOnDelete();
        });

        DB::statement('ALTER TABLE groups ADD CONSTRAINT groups_budget_complete CHECK ((budget_max_amount IS NULL) = (budget_currency IS NULL))');
        DB::statement('ALTER TABLE groups ADD CONSTRAINT groups_budget_positive CHECK (budget_max_amount IS NULL OR budget_max_amount > 0)');
        DB::statement("ALTER TABLE group_participants ADD CONSTRAINT group_participants_role_valid CHECK (role IN ('organizer', 'participant'))");
        // Exactly one organizer per group.
        DB::statement("CREATE UNIQUE INDEX group_participants_one_organizer ON group_participants (group_id) WHERE role = 'organizer'");
        DB::statement('ALTER TABLE group_exclusions ADD CONSTRAINT group_exclusions_ordered_pair CHECK (user_a_id < user_b_id)');
        DB::statement('ALTER TABLE assignments ADD CONSTRAINT assignments_no_self_draw CHECK (giver_id <> receiver_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('group_exclusions');
        Schema::dropIfExists('group_participants');
        Schema::dropIfExists('groups');
    }
};
