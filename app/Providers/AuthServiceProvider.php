<?php

namespace App\Providers;

use App\Models\Appeal;
use App\Models\Auction;
use App\Models\User;
use App\Policies\AppealPolicy;
use App\Policies\AuctionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Auction::class => AuctionPolicy::class,
        Appeal::class => AppealPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('SUPER_ADMIN')) {
                return true;
            }
        });
    }
}
