import { usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import type { TranslationKey } from '@/types/translation-keys';

export type { TranslationKey };

export type Replacements = Record<string, string | number>;

export type LocaleInfo = {
    current: string;
    bcp47: string;
    available: { code: string; name: string }[];
};

export type Translations = Partial<Record<TranslationKey, string>>;

/**
 * Replaces Laravel-style placeholders (`:name`, `:Name`, `:NAME`) so the same
 * strings work in PHP (`__()`) and in React (`t()`).
 */
export function replacePlaceholders(
    line: string,
    replacements: Replacements = {},
): string {
    return Object.entries(replacements).reduce((result, [key, raw]) => {
        const value = String(raw);

        return result
            .replaceAll(`:${key.toUpperCase()}`, value.toUpperCase())
            .replaceAll(
                `:${key.charAt(0).toUpperCase()}${key.slice(1)}`,
                value.charAt(0).toUpperCase() + value.slice(1),
            )
            .replaceAll(`:${key}`, value);
    }, line);
}

/**
 * Picks the plural form, using the same syntax as Laravel's trans_choice:
 * "one|many", or explicit ranges such as "{0} none|{1} one|[2,*] many".
 */
export function choosePlural(line: string, count: number): string {
    const segments = line.split('|');

    for (const segment of segments) {
        const match = /^\s*(\{\d+\}|\[\d+,\s*(?:\d+|\*)\])\s*(.*)$/su.exec(
            segment,
        );

        if (!match) {
            continue;
        }

        const [, condition, text] = match;

        if (condition.startsWith('{')) {
            if (Number(condition.slice(1, -1)) === count) {
                return text;
            }

            continue;
        }

        const [from, to] = condition
            .slice(1, -1)
            .split(',')
            .map((part) => part.trim());

        if (count >= Number(from) && (to === '*' || count <= Number(to))) {
            return text;
        }
    }

    const plain = segments.map((segment) =>
        segment.replace(/^\s*(\{\d+\}|\[\d+,\s*(?:\d+|\*)\])\s*/u, ''),
    );

    return count === 1 || plain.length === 1 ? plain[0] : plain[1];
}

let currentAppName = '';

/**
 * The translated app name, for places outside React such as the document title callback.
 */
export function appName(): string {
    return currentAppName || import.meta.env.VITE_APP_NAME || 'Secret Santa';
}

export function useTranslation() {
    const { translations, locale } = usePage().props;

    currentAppName = translations['common.app_name'] ?? currentAppName;

    const t = useCallback(
        (key: TranslationKey, replacements?: Replacements): string =>
            replacePlaceholders(translations[key] ?? key, replacements),
        [translations],
    );

    const tc = useCallback(
        (
            key: TranslationKey,
            count: number,
            replacements?: Replacements,
        ): string =>
            replacePlaceholders(choosePlural(translations[key] ?? key, count), {
                count,
                ...replacements,
            }),
        [translations],
    );

    /** True when the string is a known translation key (used for mixed static/dynamic labels). */
    const has = useCallback(
        (key: string): key is TranslationKey => key in translations,
        [translations],
    );

    return useMemo(() => ({ t, tc, has, locale }), [t, tc, has, locale]);
}

/**
 * Locale-aware formatting through the browser's Intl APIs.
 */
export function useFormatters() {
    const { locale } = usePage().props;
    const tag = locale.bcp47;

    return useMemo(
        () => ({
            /** Formats a calendar date ("2026-12-24") without shifting it across time zones. */
            date: (
                value: string,
                options: Intl.DateTimeFormatOptions = { dateStyle: 'long' },
            ) => {
                const [year, month, day] = value
                    .slice(0, 10)
                    .split('-')
                    .map(Number);

                return new Intl.DateTimeFormat(tag, {
                    ...options,
                    timeZone: 'UTC',
                }).format(new Date(Date.UTC(year, month - 1, day)));
            },
            dateTime: (
                value: string,
                options: Intl.DateTimeFormatOptions = {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                },
            ) => new Intl.DateTimeFormat(tag, options).format(new Date(value)),
            /** Formats an amount stored in minor units (cents). */
            money: (amountInCents: number, currency: string) =>
                new Intl.NumberFormat(tag, {
                    style: 'currency',
                    currency,
                }).format(amountInCents / 100),
            number: (value: number) => new Intl.NumberFormat(tag).format(value),
        }),
        [tag],
    );
}
