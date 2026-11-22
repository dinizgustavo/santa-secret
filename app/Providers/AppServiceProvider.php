<?php

namespace App\Providers;

use App\Support\I18n\FrontendTranslations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FrontendTranslations::class, fn (): FrontendTranslations => new FrontendTranslations(lang_path()));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
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
