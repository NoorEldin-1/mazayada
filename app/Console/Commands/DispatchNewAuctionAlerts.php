<?php

namespace App\Console\Commands;

use App\Services\NewAuctionAlertService;
use Illuminate\Console\Command;

/**
 * Edits 26 · 28 — sends the due new-auction alert waves: Premium subscribers as
 * soon as an auction is public, everyone else after the configured delay.
 */
class DispatchNewAuctionAlerts extends Command
{
    protected $signature = 'auctions:dispatch-alerts';

    protected $description = 'Send due new-auction alerts (Premium first, others after the delay)';

    public function handle(NewAuctionAlertService $alerts): void
    {
        $counts = $alerts->dispatchDue();

        $this->info("Premium alerts: {$counts['premium']}, public alerts: {$counts['public']}.");
    }
}
