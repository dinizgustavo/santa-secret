<?php

namespace App\Http\Requests\Groups;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGiftRuleRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gift_rule' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['gift_rule' => __('groups.attributes.gift_rule')];
    }

    public function giftRule(): ?string
    {
        $rule = trim($this->string('gift_rule')->toString());

        return $rule === '' ? null : $rule;
    }
}
