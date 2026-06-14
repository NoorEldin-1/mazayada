<?php

namespace App\Services;

use App\Enums\AuctionStatus;
use App\Events\AuctionClosed;
use App\Models\Auction;
use App\Models\AuditLog;
use App\Models\Bid;
use Illuminate\Support\Facades\DB;

class AuctionService
{
    public function __construct(
        private readonly BidderAliasService $aliases,
        private readonly FeeCalculator $fees,
        private readonly DocumentService $documents,
        private readonly NotificationService $notifications,
    ) {}

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

    /**
     * Close the auction (spec §4 steps 5-6): pick the winner (applying the
     * original-owner tie priority — §6.4), record the close, broadcast, and —
     * when there is a winner — declare the award (fee breakdown + signed PDF)
     * and notify everyone.
     */
    public function close(Auction $auction): void
    {
        if (! in_array($auction->status, [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED], true)) {
            return;
        }

        // Tracks whether THIS call performed the close. Concurrent callers (the
        // cron + lazy close-on-view, or two simultaneous visitors) all pass the
        // status check above before any acquires the lock; the re-check under
        // the row lock ensures only the first actually closes/awards.
        $didClose = false;

        $winningBid = DB::transaction(function () use ($auction, &$didClose) {
            /** @var Auction $locked */
            $locked = Auction::lockForUpdate()->findOrFail($auction->id);

            // Re-check under the lock: a concurrent process may have finalised
            // this auction already — bail without re-awarding (a null winningBid
            // is also legitimate for a no-bid close, so we can't rely on it).
            if (! in_array($locked->status, [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED], true)) {
                return null;
            }

            $didClose = true;
            $winningBid = $this->resolveWinningBid($locked);

            $locked->update([
                'status' => AuctionStatus::CLOSED,
                'winner_user_id' => $winningBid?->user_id,
                'final_price' => $winningBid?->amount ?? $locked->opening_price,
                'closed_at' => now(),
            ]);

            AuditLog::log('AUCTION_CLOSED', 'auction', $locked->id, 'system', 'SYSTEM', [
                'winner' => $winningBid?->user_id,
                'final_price' => $winningBid?->amount,
            ]);

            return $winningBid;
        });

        // Someone else finalised it between our status check and the lock —
        // don't broadcast or declare the outcome twice.
        if (! $didClose) {
            return;
        }

        $auction->refresh();

        // Broadcast realtime close (spec §6) — the event existed but was never dispatched.
        $winnerAlias = $winningBid
            ? $this->aliases->aliasFor($winningBid->user_id, $auction->id)
            : null;
        AuctionClosed::dispatch($auction, $winnerAlias, (int) $auction->final_price);

        $this->declareOutcome($auction, $winningBid);
    }

    public function cancel(Auction $auction): void
    {
        $auction->update(['status' => AuctionStatus::CANCELLED]);
        AuditLog::log('AUCTION_CANCELLED', 'auction', $auction->id);
    }

    /**
     * Highest valid bid wins. On a tie at the top amount, the original owner
     * (designated by the Huissier via original_owner_nin) wins; otherwise the
     * earliest bid at that amount (spec §6.4).
     */
    private function resolveWinningBid(Auction $auction): ?Bid
    {
        $maxAmount = $auction->bids()->where('is_valid', true)->max('amount');

        if ($maxAmount === null) {
            return null;
        }

        $topBids = $auction->bids()
            ->where('is_valid', true)
            ->where('amount', $maxAmount)
            ->with('user:id,nin')
            ->orderBy('bid_time')
            ->get();

        if ($auction->original_owner_nin) {
            $ownerBid = $topBids->first(
                fn (Bid $bid) => $bid->user && $bid->user->nin === $auction->original_owner_nin
            );
            if ($ownerBid) {
                return $ownerBid;
            }
        }

        return $topBids->first();
    }

    /**
     * Post-close side effects (outside the DB transaction): award declaration
     * (§6), winner/loser notifications (§10.1), and the winner's payment deadline.
     */
    private function declareOutcome(Auction $auction, ?Bid $winningBid): void
    {
        if ($winningBid && $auction->winner) {
            $fees = $this->fees->forAward($auction, (int) $auction->final_price);
            $this->documents->generateAward($auction, $fees);

            $this->notifications->auctionWon($auction->winner, $auction);
            $this->notifications->finalPaymentDue($auction->winner, $auction);
        }

        // Notify the non-winning participants that the auction ended.
        $auction->participants()
            ->with('user')
            ->where('user_id', '!=', $auction->winner_user_id)
            ->get()
            ->each(function ($participant) use ($auction) {
                if ($participant->user) {
                    $this->notifications->auctionLost($participant->user, $auction);
                }
            });
    }
}
