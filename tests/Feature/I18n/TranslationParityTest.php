<?php

use App\Enums\Locale;
use App\Support\I18n\FrontendTranslations;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

/**
 * Every translation key that exists in one locale must exist in all the others,
 * with the same :placeholders. This covers PHP files (including lang/{locale}/ui)
 * and the JSON files used by framework strings.
 *
 * @return array<string, string> flattened "file.key" => line
 */
function translationLines(string $locale): array
{
    $root = lang_path($locale);
    $lines = [];

    foreach (Finder::create()->files()->in($root)->name('*.php')->sortByName() as $file) {
        $namespace = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getRelativePathname(), 0, -4));
        $content = require $file->getRealPath();

        foreach (Arr::dot(is_array($content) ? $content : []) as $key => $value) {
            $lines["{$namespace}.{$key}"] = is_string($value) ? $value : json_encode($value);
        }
    }

    $json = lang_path("{$locale}.json");

    if (is_file($json)) {
        foreach (json_decode((string) file_get_contents($json), true, flags: JSON_THROW_ON_ERROR) as $key => $value) {
            $lines["json:{$key}"] = (string) $value;
        }
    }

    return $lines;
}

/**
 * @return list<string>
 */
function placeholdersIn(string $line): array
{
    preg_match_all('/:([a-z_]+)/i', $line, $matches);

    $names = array_map('strtolower', $matches[1]);
    sort($names);

    return array_values(array_unique($names));
}

// Every locale is compared with the fallback (comparing the fallback with itself is a harmless no-op).
dataset('locales', fn () => Locale::values());

test('every locale has a lang folder', function (string $locale) {
    expect(is_dir(lang_path($locale)))->toBeTrue("Missing lang/{$locale}");
})->with('locales');

test('locales have exactly the same translation keys', function (string $locale) {
    $reference = array_keys(translationLines(Locale::fallback()->value));
    $candidate = array_keys(translationLines($locale));

    $missing = array_values(array_diff($reference, $candidate));
    $extra = array_values(array_diff($candidate, $reference));

    expect($missing)->toBe([], "Keys missing in {$locale}: ".implode(', ', $missing))
        ->and($extra)->toBe([], "Keys only in {$locale}: ".implode(', ', $extra));
})->with('locales');

test('translations keep the same placeholders', function (string $locale) {
    $reference = translationLines(Locale::fallback()->value);
    $candidate = translationLines($locale);
    $mismatches = [];

    foreach (array_intersect_key($reference, $candidate) as $key => $line) {
        if (placeholdersIn($line) !== placeholdersIn($candidate[$key])) {
            $mismatches[] = $key;
        }
    }

    expect($mismatches)->toBe([], "Placeholder mismatch in {$locale}: ".implode(', ', $mismatches));
})->with('locales');

test('UI translations are never empty', function (string $locale) {
    $empty = array_keys(array_filter(
        app(FrontendTranslations::class)->load(Locale::from($locale)),
        fn (string $line) => trim($line) === '',
    ));

    expect($empty)->toBe([]);
})->with('locales');

test('the generated TypeScript keys are up to date', function () {
    $this->artisan('i18n:types', ['--check' => true])->assertSuccessful();
});
