<?php

namespace App\Console\Commands;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Console\Command;

/**
 * §10.1 — remind a winner whose final-payment deadline is approaching and who
 * has not yet paid. Runs daily; reminds when within the configured window.
 */
class RemindFinalPayment extends Command
{
    protected $signature = 'auctions:remind-final-payment';
    protected $description = 'Remind winners with an approaching final-payment deadline';

    public function handle(PaymentService $payments, NotificationService $notifications): void
    {
        $window = (int) setting('payments.reminder_days_before_deadline',
            config('mazayada.payments.reminder_days_before_deadline', 1));

        $auctions = Auction::where('status', AuctionStatus::CLOSED)
            ->whereNotNull('winner_user_id')
            ->whereNull('settled_at')
            ->whereNotNull('closed_at')
            ->with('winner')
            ->get();

        $reminded = 0;

        foreach ($auctions as $auction) {
            if (! $auction->winner) {
                continue;
            }

            $deadline = $auction->closed_at->copy()->addDays($auction->finalPaymentDeadlineDays());
            $remindFrom = $deadline->copy()->subDays($window);

            if (now()->lessThan($remindFrom) || now()->greaterThan($deadline)) {
                continue;
            }

            if ($payments->confirmedFinalPayment($auction, $auction->winner)) {
                continue;
            }

            $notifications->finalPaymentDue($auction->winner, $auction);
            $reminded++;
        }

        $this->info("Reminded {$reminded} winner(s).");
    }
}
