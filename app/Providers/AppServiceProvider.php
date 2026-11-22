<?php

namespace App\Providers;

use App\Draw\DrawSolver;
use App\Models\User;
use App\Support\I18n\FrontendTranslations;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Random\Randomizer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FrontendTranslations::class, fn (): FrontendTranslations => new FrontendTranslations(lang_path()));

        // Default engine is CSPRNG-backed; tests swap in a seeded engine for reproducible draws.
        $this->app->bind(DrawSolver::class, fn (): DrawSolver => new DrawSolver(new Randomizer));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Limits for the group endpoints that matter (the auth ones live in FortifyServiceProvider).
     */
    protected function configureRateLimiting(): void
    {
        $byUser = static fn (Request $request): string => $request->user() instanceof User
            ? 'user|'.$request->user()->id
            : 'ip|'.$request->ip();

        RateLimiter::for('join', fn (Request $request): Limit => Limit::perMinute(10)->by($byUser($request)));

        RateLimiter::for('draw', fn (Request $request): Limit => Limit::perMinute(5)->by($byUser($request)));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Outside production, fail loudly on lazy loading (N+1), unknown attributes and silently discarded fills.
        Model::shouldBeStrict(! $this->app->isProduction());

        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );

        // Friends sign up from their phones, so keep the rule reasonable but still reject leaked passwords.
        Password::defaults(fn (): ?Password => $this->app->isProduction()
            ? Password::min(8)
                ->letters()
                ->numbers()
                ->uncompromised()
            : null,
        );
    }
}
