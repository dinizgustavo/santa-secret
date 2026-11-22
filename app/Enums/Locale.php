<?php

namespace App\Enums;

/**
 * Locales the UI is translated into.
 *
 * Adding a language means adding a case here and a matching folder in lang/
 * (the parity test will tell you which keys are still missing).
 */
enum Locale: string
{
    case PortugueseBrazil = 'pt_BR';
    case English = 'en';
    case Spanish = 'es';

    /**
     * The language name written in the language itself, so people can always find their own.
     */
    public function nativeName(): string
    {
        return match ($this) {
            self::PortugueseBrazil => 'Português (Brasil)',
            self::English => 'English',
            self::Spanish => 'Español',
        };
    }

    /**
     * BCP 47 tag used by the browser's Intl APIs and the <html lang> attribute.
     */
    public function bcp47(): string
    {
        return str_replace('_', '-', $this->value);
    }

    public static function default(): self
    {
        return self::tryFrom(config()->string('app.locale')) ?? self::PortugueseBrazil;
    }

    public static function fallback(): self
    {
        return self::tryFrom(config()->string('app.fallback_locale')) ?? self::English;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $locale): string => $locale->value, self::cases());
    }
}
