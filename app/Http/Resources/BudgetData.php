<?php

namespace App\Http\Resources;

use App\Models\Group;

final class BudgetData
{
    /**
     * @return array{amount: int, currency: string}|null amount in cents
     */
    public static function from(Group $group): ?array
    {
        if ($group->budget_max_amount === null || $group->budget_currency === null) {
            return null;
        }

        return [
            'amount' => $group->budget_max_amount,
            'currency' => $group->budget_currency->value,
        ];
    }
}
