<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Who gives a gift to whom. Each person may only ever see the row where they are the giver.
 *
 * @property int $id
 * @property int $group_id
 * @property int $giver_id
 * @property int $receiver_id
 * @property CarbonImmutable|null $created_at
 * @property-read User $receiver
 */
#[Fillable(['group_id', 'giver_id', 'receiver_id'])]
#[Hidden(['giver_id', 'receiver_id'])]
class Assignment extends Model
{
    public const UPDATED_AT = null;

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
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
