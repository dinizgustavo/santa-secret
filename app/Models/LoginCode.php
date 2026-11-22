<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $email
 * @property string $code_hash
 * @property int $attempts
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property Carbon|null $created_at
 */
#[Fillable(['email', 'code_hash', 'attempts', 'expires_at', 'consumed_at'])]
class LoginCode extends Model
{
    public const UPDATED_AT = null;

    /**
     * Codes that can still be used: not consumed and not expired.
     *
     * @param  Builder<self>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }

    /**
     * Binds the code to the e-mail address and the app key, so a leaked table is useless on its own.
     */
    public static function hashFor(string $email, string $code): string
    {
        return hash_hmac('sha256', strtolower($email).'|'.$code, config()->string('app.key'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
