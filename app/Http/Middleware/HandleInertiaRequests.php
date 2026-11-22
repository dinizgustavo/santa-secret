<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Models\User;
use App\Support\I18n\FrontendTranslations;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(private readonly FrontendTranslations $translations) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * Every value here reaches every page, so only whitelisted fields are shared.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = Locale::tryFrom(app()->getLocale()) ?? Locale::default();
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user instanceof User ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                ] : null,
            ],
            'locale' => [
                'current' => $locale->value,
                'bcp47' => $locale->bcp47(),
                'available' => array_map(
                    static fn (Locale $option): array => ['code' => $option->value, 'name' => $option->nativeName()],
                    Locale::cases(),
                ),
            ],
            // Sent once and cached by the Inertia client; the key changes with the locale or the files.
            'translations' => Inertia::once(fn (): array => $this->translations->for($locale))
                ->as("translations.{$locale->value}.{$this->translations->fingerprint($locale)}"),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
