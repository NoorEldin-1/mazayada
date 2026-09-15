<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

/**
 * Edits 24-25 — expire finished Premium subscriptions and remind those close to
 * their end (subscription_expired / subscription_expiring notifications).
 */
class SweepSubscriptions extends Command
{
    protected $signature = 'subscriptions:sweep';

    protected $description = 'Expire ended Premium subscriptions and send expiry reminders';

    public function handle(SubscriptionService $subscriptions): void
    {
        [$expired, $reminded] = $subscriptions->sweep();

        $this->info("Expired {$expired} subscription(s), reminded {$reminded}.");
    }
}
