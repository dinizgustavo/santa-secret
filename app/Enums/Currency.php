<?php

namespace App\Enums;

/**
 * Currencies offered for the spending limit. All use two decimal places, so amounts are stored in cents.
 */
enum Currency: string
{
    case BRL = 'BRL';
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case MXN = 'MXN';
    case ARS = 'ARS';

    /**
     * A sensible pre-selection for a new group, based on the UI language.
     */
    public static function defaultFor(Locale $locale): self
    {
        return match ($locale) {
            Locale::PortugueseBrazil => self::BRL,
            Locale::Spanish => self::EUR,
            Locale::English => self::USD,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $currency): string => $currency->value, self::cases());
    }
}
