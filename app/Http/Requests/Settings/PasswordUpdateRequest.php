<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Accounts created through Google or an e-mailed code have no password yet,
     * so they can set one without a current password.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $hasPassword = $user instanceof User && $user->hasPassword();

        return [
            ...($hasPassword ? ['current_password' => $this->currentPasswordRules()] : []),
            'password' => $this->passwordRules(),
        ];
    }
}
