<?php

namespace App\Models;

use App\Enums\ParticipantRole;
use Carbon\CarbonImmutable;
use Database\Factories\GroupParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $group_id
 * @property int $user_id
 * @property ParticipantRole $role
 * @property string|null $gift_rule
 * @property CarbonImmutable $joined_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Group $group
 * @property-read User $user
 */
#[Fillable(['group_id', 'user_id', 'role', 'gift_rule', 'joined_at'])]
class GroupParticipant extends Model
{
    /** @use HasFactory<GroupParticipantFactory> */
    use HasFactory;

    public function isOrganizer(): bool
    {
        return $this->role === ParticipantRole::Organizer;
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ParticipantRole::class,
            'joined_at' => 'datetime',
        ];
    }
}
