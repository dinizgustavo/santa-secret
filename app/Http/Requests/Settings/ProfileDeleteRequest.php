<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfileDeleteRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * People with a password confirm with it; passwordless accounts type their e-mail address instead.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        if ($user instanceof User && ! $user->hasPassword()) {
            return [
                'confirmation' => ['required', 'string', Rule::in([$user->email])],
            ];
        }

        return [
            'password' => $this->currentPasswordRules(),
        ];
    }

    /**
     * Deleting an account that is part of a finished draw would silently break someone else's match.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();

                if (! $user instanceof User) {
                    return;
                }

                $drawnGroup = Group::query()
                    ->whereNotNull('drawn_at')
                    ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
                    ->first();

                if ($drawnGroup !== null) {
                    $validator->errors()->add(
                        $user->hasPassword() ? 'password' : 'confirmation',
                        __('groups.errors.in_drawn_group', ['group' => $drawnGroup->name]),
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('confirmation'))) {
            $this->merge(['confirmation' => mb_strtolower(trim($this->input('confirmation')))]);
        }
    }
}
