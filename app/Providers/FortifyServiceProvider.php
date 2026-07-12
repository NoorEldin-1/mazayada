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

        // Route-level brute-force guard for POST /login. It throttles only regular
        // citizens (and unknown inputs) — staff accounts (admin / entity /
        // entity-staff) are never rate-limited so an operator can't self-lock.
        // NOTE: the login form field is `nin_or_email`, not Fortify's `nin`
        // username — key off that so attempts are scoped per-account, not lumped
        // together per-IP under an empty key.
        RateLimiter::for('login', function (Request $request) {
            $identifier = (string) $request->input('nin_or_email');

            $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'nin';
            $user = $identifier !== ''
                ? \App\Models\User::where($field, $identifier)->first()
                : null;

            if ($user && ! $user->isThrottleable()) {
                return Limit::none();
            }

            $throttleKey = Str::transliterate(Str::lower($identifier).'|'.$request->ip());

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
