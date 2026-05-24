<?php

namespace App\Console\Commands;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Services\AuctionService;
use Illuminate\Console\Command;

class CloseExpiredAuctions extends Command
{
    protected $signature = 'auctions:close';
    protected $description = 'Close auctions that have passed their end time';

    public function handle(AuctionService $service): void
    {
        $auctions = Auction::whereIn('status', [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED])
            ->where('end_time', '<=', now())
            ->get();

        foreach ($auctions as $auction) {
            $service->close($auction);
            $this->info("Closed auction: {$auction->id}");
        }

        $this->info("Closed {$auctions->count()} auction(s).");
    }
}
