<?php

namespace App\Support\I18n;

use App\Enums\Locale;
use Illuminate\Support\Arr;

/**
 * Loads the translation files that are shared with the React frontend.
 *
 * Only files under lang/{locale}/ui/ are sent to the browser, which keeps the
 * payload small and makes it explicit which strings are UI strings. Keys are
 * flattened to dot notation, e.g. lang/en/ui/auth.php ['login' => ['title' => ..]]
 * becomes "auth.login.title".
 */
final class FrontendTranslations
{
    public const DIRECTORY = 'ui';

    /** @var array<string, array<string, string>> */
    private array $loaded = [];

    public function __construct(private readonly string $langPath) {}

    /**
     * Translations for the given locale, with any missing key filled from the fallback locale.
     *
     * @return array<string, string>
     */
    public function for(Locale $locale): array
    {
        $fallback = Locale::fallback();

        if ($locale === $fallback) {
            return $this->load($locale);
        }

        return array_replace($this->load($fallback), $this->load($locale));
    }

    /**
     * Raw, flattened translations of a single locale (no fallback applied).
     *
     * @return array<string, string>
     */
    public function load(Locale $locale): array
    {
        if (isset($this->loaded[$locale->value])) {
            return $this->loaded[$locale->value];
        }

        $translations = [];

        foreach ($this->files($locale) as $namespace => $file) {
            $lines = require $file;

            if (! is_array($lines)) {
                continue;
            }

            foreach (Arr::dot($lines) as $key => $value) {
                if (is_string($value)) {
                    $translations["{$namespace}.{$key}"] = $value;
                }
            }
        }

        ksort($translations);

        return $this->loaded[$locale->value] = $translations;
    }

    /**
     * A cheap fingerprint of the UI translation files, used to bust the client-side cache
     * whenever any of them changes.
     */
    public function fingerprint(Locale $locale): string
    {
        $parts = [];

        foreach ([Locale::fallback(), $locale] as $source) {
            foreach ($this->files($source) as $file) {
                $parts[] = $file.':'.filemtime($file);
            }
        }

        return substr(hash('xxh3', implode('|', $parts)), 0, 12);
    }

    /**
     * @return array<string, string> namespace => absolute path
     */
    private function files(Locale $locale): array
    {
        $paths = glob($this->langPath.DIRECTORY_SEPARATOR.$locale->value.DIRECTORY_SEPARATOR.self::DIRECTORY.DIRECTORY_SEPARATOR.'*.php');

        $files = [];

        foreach ($paths === false ? [] : $paths as $path) {
            $files[pathinfo($path, PATHINFO_FILENAME)] = $path;
        }

        ksort($files);

        return $files;
    }
}
