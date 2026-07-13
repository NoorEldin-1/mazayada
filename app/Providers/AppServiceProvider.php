<?php

namespace App\Providers;

use App\Enums\CommercialRegisterStatus;
use App\Enums\KycStatus;
use App\Models\AuctionReport;
use App\Models\CommercialRegister;
use App\Models\User;
use App\Services\Payments\PaymentDriver;
use App\Services\Payments\PaymentGatewayInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Payment gateway driver (mock | chargily | cibweb) — resolved from a
        // single source of truth so the bound gateway always matches the value
        // PaymentService records on payments.gateway. See PaymentDriver.
        $this->app->bind(PaymentGatewayInterface::class, fn () => PaymentDriver::make());
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

        // Sidebar badge: number of KYC submissions awaiting review. Only queried
        // for reviewers; a single indexed COUNT (no cache) so the badge drops the
        // moment a request is approved/rejected.
        View::composer('layouts.admin', function ($view) {
            $user = auth()->user();

            $view->with('kycPendingCount', $user?->can('kyc.review')
                ? User::where('kyc_status', KycStatus::UNDER_REVIEW)->count()
                : 0);

            // Same treatment for the Commercial Register queue badge.
            $view->with('commercialRegisterPendingCount', $user?->can('commercial-register.review')
                ? CommercialRegister::where('status', CommercialRegisterStatus::PENDING)->count()
                : 0);

            // Auction-reports badge: for platform admins, the number of issued
            // reports still awaiting referral to their organising entity — an
            // actionable nudge. whereHas('auction') respects EntityScope.
            $view->with('auctionReportPendingCount', ($user?->entity_id === null && $user?->can('auction-reports.refer'))
                ? AuctionReport::whereHas('auction')->whereNull('referred_to_entity_at')->count()
                : 0);
        });
    }
}
