<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(config('mazayada.security.login_max_attempts', 5))->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Bid placement throttle — prevent bid flooding per user per auction.
        RateLimiter::for('bidding', function (Request $request) {
            $userId = optional($request->user())->id ?? $request->ip();
            $auctionId = $request->route('auction')?->id ?? 'global';

            return Limit::perMinute(config('mazayada.bidding.max_per_minute', 10))->by($userId.'|'.$auctionId);
        });

        // Mobile API limiters. 'api' is referenced by the framework `api`
        // middleware group (throttle:api) and MUST be defined. 'api-auth' and
        // 'api-otp' are applied to the sensitive auth routes on top of it.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) setting('api.rate.default', 60))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute((int) setting('api.rate.auth', 10))->by($request->ip());
        });

        RateLimiter::for('api-otp', function (Request $request) {
            $identifier = (string) ($request->input('user_id') ?? $request->input('nin') ?? $request->input('nin_or_email') ?? '');

            return Limit::perMinute((int) setting('api.rate.otp', 5))->by($request->ip().'|'.$identifier);
        });

        // Identify users by their NIN (Algerian National ID) rather than email.
        Fortify::username('nin');
        Fortify::email('email');
    }
}
