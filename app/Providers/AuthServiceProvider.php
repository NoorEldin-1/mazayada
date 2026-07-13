<?php

namespace App\Providers;

use App\Models\Appeal;
use App\Models\Auction;
use App\Models\AuctionReport;
use App\Models\Document;
use App\Models\User;
use App\Policies\AppealPolicy;
use App\Policies\AuctionPolicy;
use App\Policies\AuctionReportPolicy;
use App\Policies\DocumentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Auction::class => AuctionPolicy::class,
        Appeal::class => AppealPolicy::class,
        AuctionReport::class => AuctionReportPolicy::class,
        Document::class => DocumentPolicy::class,
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
