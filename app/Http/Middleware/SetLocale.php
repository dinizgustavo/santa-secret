<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale: saved user preference, then session, then the
 * browser's Accept-Language header, then the application default.
 */
class SetLocale
{
    public const SESSION_KEY = 'locale';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        App::setLocale($locale->value);
        Carbon::setLocale($locale->value);

        return $next($request);
    }

    private function resolve(Request $request): Locale
    {
        $user = $request->user();

        if ($user instanceof User && $user->locale !== null) {
            return $user->locale;
        }

        $fromSession = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;

        if (is_string($fromSession) && ($locale = Locale::tryFrom($fromSession)) !== null) {
            return $locale;
        }

        return $this->fromAcceptLanguage($request) ?? Locale::default();
    }

    /**
     * Matches the browser languages, in preference order, against the supported locales:
     * an exact match first ("pt_BR"), then the same language in any region ("es_MX" -> "es").
     *
     * Symfony's getPreferredLanguage() is not used because it returns the first supported
     * locale when nothing matches, which would silently bypass the configured default.
     */
    private function fromAcceptLanguage(Request $request): ?Locale
    {
        foreach ($request->getLanguages() as $language) {
            if (($exact = Locale::tryFrom($language)) !== null) {
                return $exact;
            }

            $primary = strtolower(explode('_', $language)[0]);

            foreach (Locale::cases() as $locale) {
                if (strtolower(explode('_', $locale->value)[0]) === $primary) {
                    return $locale;
                }
            }
        }

        return null;
    }
}
