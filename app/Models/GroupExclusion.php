<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Two people who must not draw each other (in either direction).
 *
 * @property int $id
 * @property int $group_id
 * @property int $user_a_id always the smaller user id of the pair
 * @property int $user_b_id
 * @property CarbonImmutable|null $created_at
 * @property-read User $userA
 * @property-read User $userB
 */
#[Fillable(['group_id', 'user_a_id', 'user_b_id'])]
class GroupExclusion extends Model
{
    public const UPDATED_AT = null;

    /**
     * Normalizes a pair so (a, b) and (b, a) are the same row.
     *
     * @return array{user_a_id: int, user_b_id: int}
     */
    public static function orderedPair(int $first, int $second): array
    {
        return ['user_a_id' => min($first, $second), 'user_b_id' => max($first, $second)];
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
    public function userA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_a_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function userB(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_b_id');
    }
}
