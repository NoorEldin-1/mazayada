<?php

namespace App\Console\Commands;

use App\Enums\AuctionStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Console\Command;

/**
 * §4 step 8 — settle deposits on closed auctions once the winner's final-payment
 * deadline has passed:
 *  - losers: refund their confirmed deposit,
 *  - winner who paid: keep (already deducted in the final payment),
 *  - winner who defaulted: forfeit the deposit + blacklist.
 *
 * Idempotent via auctions.settled_at.
 */
class SettleAuctionDeposits extends Command
{
    protected $signature = 'auctions:settle-deposits';
    protected $description = 'Refund losing deposits and forfeit defaulting winners on closed auctions';

    public function handle(PaymentService $payments, NotificationService $notifications): void
    {
        $auctions = Auction::where('status', AuctionStatus::CLOSED)
            ->whereNull('settled_at')
            ->whereNotNull('closed_at')
            ->get();

        $settled = 0;

        foreach ($auctions as $auction) {
            $deadline = $auction->closed_at->copy()->addDays($auction->finalPaymentDeadlineDays());

            // Wait until the winner's deadline has elapsed before settling.
            if (now()->lessThan($deadline)) {
                continue;
            }

            $this->settle($auction, $payments, $notifications);
            $auction->update(['settled_at' => now()]);
            $settled++;
            $this->info("Settled auction: {$auction->id}");
        }

        $this->info("Settled {$settled} auction(s).");
    }

    private function settle(Auction $auction, PaymentService $payments, NotificationService $notifications): void
    {
        $winnerPaid = $auction->winner_user_id
            && $payments->confirmedFinalPayment($auction, $auction->winner);

        // Confirmed deposits for this auction.
        $deposits = Payment::where('auction_id', $auction->id)
            ->where('payment_type', PaymentType::DEPOSIT)
            ->where('status', PaymentStatus::CONFIRMED)
            ->with('user')
            ->get();

        foreach ($deposits as $deposit) {
            $isWinner = $deposit->user_id === $auction->winner_user_id;

            if (! $isWinner) {
                // Loser — refund.
                if ($payments->refundDeposit($deposit) && $deposit->user) {
                    $notifications->depositRefunded($deposit->user, $auction, (int) $deposit->amount);
                }

                continue;
            }

            // Winner who defaulted on the final payment — forfeit + blacklist.
            if (! $winnerPaid) {
                $payments->forfeitDeposit($deposit);
                $this->blacklistDefaulter($auction, $deposit->user_id);
                if ($deposit->user) {
                    $notifications->depositForfeited($deposit->user, $auction);
                }
            }
        }
    }

    private function blacklistDefaulter(Auction $auction, ?string $userId): void
    {
        if (! $userId) {
            return;
        }

        $user = $auction->winner;
        if ($user && ! $user->is_blacklisted) {
            $user->update([
                'is_blacklisted' => true,
                'blacklist_reason' => __('payments.forfeit_blacklist_reason'),
            ]);
            invalidate_user_sessions($userId);
        }

        AuctionParticipant::where('auction_id', $auction->id)
            ->where('user_id', $userId)
            ->update(['blacklisted_for_default' => true]);

        AuditLog::log('WINNER_DEFAULTED', 'Auction', $auction->id, 'system', 'SYSTEM', [
            'user_id' => $userId,
        ]);
    }
}
