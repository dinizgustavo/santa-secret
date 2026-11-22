<?php

namespace App\Http\Requests\Groups;

use App\Enums\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared by create and edit. Authorization happens in the controller through GroupPolicy.
 */
class GroupRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            // Typed in major units ("50" or "49.90"); stored in cents.
            'budget_max' => ['nullable', 'numeric', 'decimal:0,2', 'min:1', 'max:100000'],
            'budget_currency' => ['nullable', 'required_with:budget_max', Rule::enum(Currency::class)],
            'event_date' => ['required', 'date_format:Y-m-d', $this->isMethod('post') ? 'after_or_equal:today' : 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('groups.attributes.name'),
            'description' => __('groups.attributes.description'),
            'budget_max' => __('groups.attributes.budget_max'),
            'budget_currency' => __('groups.attributes.budget_currency'),
            'event_date' => __('groups.attributes.event_date'),
        ];
    }

    /**
     * @return array{name: string, description: string|null, budget_max_amount: int|null, budget_currency: string|null, event_date: string}
     */
    public function toAttributes(): array
    {
        $budget = $this->filled('budget_max') ? (int) round($this->float('budget_max') * 100) : null;
        $description = trim($this->string('description')->toString());

        return [
            'name' => trim($this->string('name')->toString()),
            'description' => $description === '' ? null : $description,
            'budget_max_amount' => $budget,
            'budget_currency' => $budget === null ? null : $this->string('budget_currency')->toString(),
            'event_date' => $this->string('event_date')->toString(),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Accept "49,90" from Portuguese and Spanish keyboards.
        if (is_string($this->input('budget_max'))) {
            $this->merge(['budget_max' => str_replace(',', '.', trim($this->input('budget_max')))]);
        }
    }
}
