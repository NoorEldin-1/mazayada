<?php

namespace App\Providers;

use App\Services\Payments\CibWebGateway;
use App\Services\Payments\MockPaymentGateway;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Payment gateway: mock by default (spec §7 / CIBWEB_MOCK=true), real
        // CIBWeb/SATIM client once credentials are configured and mock is off.
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return setting('payments.mock', config('mazayada.payments.mock', true))
                ? new MockPaymentGateway()
                : new CibWebGateway();
        });
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
