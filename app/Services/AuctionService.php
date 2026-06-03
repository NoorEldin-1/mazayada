<?php

namespace App\Services;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class AuctionService
{
    public function publish(Auction $auction): void
    {
        if ($auction->status !== AuctionStatus::DRAFT) {
            throw new \Exception(__('admin.flash.auction_publish_only_draft'));
        }
        $auction->update(['status' => AuctionStatus::PUBLISHED]);
        AuditLog::log('AUCTION_PUBLISHED', 'auction', $auction->id);
    }

    public function start(Auction $auction): void
    {
        if ($auction->status !== AuctionStatus::PUBLISHED) {
            throw new \Exception(__('admin.flash.auction_start_only_published'));
        }
        $auction->update(['status' => AuctionStatus::ACTIVE]);
        AuditLog::log('AUCTION_STARTED', 'auction', $auction->id);
    }

    public function close(Auction $auction): void
    {
        if (!in_array($auction->status, [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED])) {
            return;
        }

        DB::transaction(function () use ($auction) {
            $auction = Auction::lockForUpdate()->findOrFail($auction->id);

            $winningBid = $auction->bids()
                ->where('is_valid', true)
                ->orderByDesc('amount')
                ->first();

            $auction->update([
                'status' => AuctionStatus::CLOSED,
                'winner_user_id' => $winningBid?->user_id,
                'final_price' => $winningBid?->amount ?? $auction->opening_price,
            ]);

            AuditLog::log('AUCTION_CLOSED', 'auction', $auction->id, 'system', 'SYSTEM', [
                'winner' => $winningBid?->user_id,
                'final_price' => $winningBid?->amount,
            ]);
        });
    }

    public function cancel(Auction $auction): void
    {
        $auction->update(['status' => AuctionStatus::CANCELLED]);
        AuditLog::log('AUCTION_CANCELLED', 'auction', $auction->id);
    }
}
