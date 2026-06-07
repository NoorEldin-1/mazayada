<?php

namespace App\Services;

use App\Models\Appeal;
use App\Models\Auction;
use App\Models\Delivery;
use App\Models\InspectionQuestion;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AuctionEventNotification;

/**
 * Single dispatch point for the auction lifecycle notifications (spec §10.1).
 * Every method resolves the recipient(s) and sends an AuctionEventNotification
 * (email + in-app). Mail is auto-localized via HasLocalePreference on User.
 *
 * Channels are email + in-app only for now; SMS/Push remain pluggable through
 * the AuctionEventNotification::via() contract.
 */
class NotificationService
{
    public function conditionBookPublished(Auction $auction): void
    {
        $url = route('auctions.show', $auction);
        $params = ['auction' => $auction->localizedTitle()];

        // Notify everyone watching this auction.
        $auction->loadMissing('category');
        foreach ($auction->watchers()->get() as $user) {
            $user->notify(new AuctionEventNotification('condition_book_published', $params, $url));
        }
    }

    public function paymentConfirmed(User $user, Payment $payment): void
    {
        $payment->loadMissing('auction');
        $params = [
            'auction' => $payment->auction?->localizedTitle() ?? '',
            'amount' => dzd((int) $payment->amount),
            'type' => $payment->payment_type->label(),
        ];
        $user->notify(new AuctionEventNotification('payment_confirmed', $params,
            $payment->auction ? route('auctions.show', $payment->auction) : null));
    }

    public function paymentFailed(User $user, Payment $payment): void
    {
        $payment->loadMissing('auction');
        $params = [
            'auction' => $payment->auction?->localizedTitle() ?? '',
            'type' => $payment->payment_type->label(),
        ];
        $user->notify(new AuctionEventNotification('payment_failed', $params,
            $payment->auction ? route('auctions.show', $payment->auction) : null));
    }

    public function inspectionAnswered(InspectionQuestion $question): void
    {
        $question->loadMissing(['auction', 'user']);
        if (! $question->user) {
            return;
        }
        $params = ['auction' => $question->auction?->localizedTitle() ?? ''];
        $question->user->notify(new AuctionEventNotification('inspection_answered', $params,
            $question->auction ? route('auctions.show', $question->auction) : null));
    }

    public function outbid(User $user, Auction $auction, int $newPriceCentimes): void
    {
        $params = [
            'auction' => $auction->localizedTitle(),
            'amount' => dzd($newPriceCentimes),
        ];
        $user->notify(new AuctionEventNotification('outbid', $params, route('auctions.show', $auction)));
    }

    public function auctionWon(User $user, Auction $auction): void
    {
        $params = [
            'auction' => $auction->localizedTitle(),
            'amount' => dzd((int) $auction->final_price),
            'days' => $auction->finalPaymentDeadlineDays(),
        ];
        $user->notify(new AuctionEventNotification('auction_won', $params, route('auctions.show', $auction)));
    }

    public function auctionLost(User $user, Auction $auction): void
    {
        $params = ['auction' => $auction->localizedTitle()];
        $user->notify(new AuctionEventNotification('auction_lost', $params, route('auctions.show', $auction)));
    }

    public function finalPaymentDue(User $user, Auction $auction): void
    {
        $params = [
            'auction' => $auction->localizedTitle(),
            'days' => $auction->finalPaymentDeadlineDays(),
        ];
        $user->notify(new AuctionEventNotification('final_payment_due', $params, route('auctions.show', $auction)));
    }

    public function depositRefunded(User $user, Auction $auction, int $amountCentimes): void
    {
        $params = [
            'auction' => $auction->localizedTitle(),
            'amount' => dzd($amountCentimes),
        ];
        $user->notify(new AuctionEventNotification('deposit_refunded', $params, route('auctions.show', $auction)));
    }

    public function depositForfeited(User $user, Auction $auction): void
    {
        $params = ['auction' => $auction->localizedTitle()];
        $user->notify(new AuctionEventNotification('deposit_forfeited', $params, route('auctions.show', $auction)));
    }

    public function deliveryUpdate(Delivery $delivery): void
    {
        $delivery->loadMissing(['auction', 'user']);
        if (! $delivery->user) {
            return;
        }
        $params = [
            'auction' => $delivery->auction?->localizedTitle() ?? '',
            'status' => $delivery->status->label(),
        ];
        $delivery->user->notify(new AuctionEventNotification('delivery_update', $params,
            $delivery->auction ? route('auctions.show', $delivery->auction) : null));
    }

    public function appealUpdated(Appeal $appeal): void
    {
        $appeal->loadMissing('user');
        if (! $appeal->user) {
            return;
        }
        $params = ['status' => $appeal->status->label()];
        $appeal->user->notify(new AuctionEventNotification('appeal_updated', $params, route('citizen.appeals')));
    }
}
