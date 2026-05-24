<?php

namespace App\Console\Commands;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\AuditLog;
use Illuminate\Console\Command;

class ActivateScheduledAuctions extends Command
{
    protected $signature = 'auctions:activate';
    protected $description = 'Activate published auctions whose start time has arrived';

    public function handle(): void
    {
        $auctions = Auction::where('status', AuctionStatus::PUBLISHED)
            ->where('start_time', '<=', now())
            ->get();

        foreach ($auctions as $auction) {
            $auction->update(['status' => AuctionStatus::ACTIVE]);
            AuditLog::log('AUCTION_ACTIVATED', 'auction', $auction->id, 'system', 'SYSTEM');
            $this->info("Activated auction: {$auction->id}");
        }

        $this->info("Activated {$auctions->count()} auction(s).");
    }
}
