<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(function () {
            return $this->app->isProduction()
                ? Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
                : Password::min(8);
        });

        // Strict model behavior is great for catching bugs but causes false positives
        // in tests where partial models are created. Enable only in local env.
        $strict = $this->app->environment('local');
        Model::shouldBeStrict($strict);
        Model::preventAccessingMissingAttributes($strict);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('vendor.pagination.mazayada');
        Paginator::defaultSimpleView('vendor.pagination.mazayada-simple');
    }
}
