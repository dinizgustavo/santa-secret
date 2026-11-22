<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\ParticipantRole;
use Carbon\CarbonImmutable;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A Secret Santa group.
 *
 * Never serialize this model straight into a response: the frontend receives
 * hand-picked fields from App\Http\Resources so assignments can never leak.
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string|null $description
 * @property int|null $budget_max_amount
 * @property Currency|null $budget_currency
 * @property CarbonImmutable $event_date
 * @property string $invite_token
 * @property CarbonImmutable|null $drawn_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read int|null $participants_count only after withCount('participants')
 * @property-read GroupParticipant|null $organizerParticipant
 */
#[Fillable(['name', 'description', 'budget_max_amount', 'budget_currency', 'event_date'])]
#[Hidden(['id', 'invite_token'])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    /** A group of friends, not a company: keeps the draw fast and the reveal list readable. */
    public const MAX_PARTICIPANTS = 100;

    public const MIN_PARTICIPANTS_TO_DRAW = 3;

    protected static function booted(): void
    {
        static::creating(function (self $group): void {
            $group->ulid ??= Str::lower((string) Str::ulid());
            $group->invite_token ??= self::newInviteToken();
        });
    }

    public static function newInviteToken(): string
    {
        return Str::random(40);
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function isDrawn(): bool
    {
        return $this->drawn_at !== null;
    }

    /**
     * @return HasMany<GroupParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(GroupParticipant::class);
    }

    /**
     * @return HasOne<GroupParticipant, $this>
     */
    public function organizerParticipant(): HasOne
    {
        return $this->hasOne(GroupParticipant::class)->where('role', ParticipantRole::Organizer);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_participants')
            ->withPivot(['role', 'gift_rule', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<GroupExclusion, $this>
     */
    public function exclusions(): HasMany
    {
        return $this->hasMany(GroupExclusion::class);
    }

    /**
     * Internal only: read through App\Actions\Draw\FindMyAssignment, never eager-loaded for a page.
     *
     * @return HasMany<Assignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function participantFor(User $user): ?GroupParticipant
    {
        if ($this->relationLoaded('participants')) {
            return $this->participants->firstWhere('user_id', $user->id);
        }

        return $this->participants()->where('user_id', $user->id)->first();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_max_amount' => 'integer',
            'budget_currency' => Currency::class,
            'event_date' => 'date',
            'drawn_at' => 'datetime',
        ];
    }
}
