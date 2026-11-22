<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('confirmation'))) {
            $this->merge(['confirmation' => mb_strtolower(trim($this->input('confirmation')))]);
        }
    }
}
