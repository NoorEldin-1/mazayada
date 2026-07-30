<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Events\PersonalAuctionEvent;
use App\Models\Appeal;
use App\Models\Auction;
use App\Models\AuctionReport;
use App\Models\Delivery;
use App\Models\InspectionQuestion;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\AuctionEventNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
            'amount' => dzd_text((int) $payment->amount),
            'type' => $payment->payment_type->label(),
        ];
        $user->notify(new AuctionEventNotification('payment_confirmed', $params,
            $payment->auction ? route('auctions.show', $payment->auction) : null));

        $this->broadcastPersonal($user, $payment->auction, 'payment_confirmed', [
            'payment_type' => $payment->payment_type?->value,
            'amount' => dinars((int) $payment->amount),
        ]);
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

        $this->broadcastPersonal($user, $payment->auction, 'payment_failed', [
            'payment_type' => $payment->payment_type?->value,
        ]);
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
            'amount' => dzd_text($newPriceCentimes),
        ];
        $user->notify(new AuctionEventNotification('outbid', $params, route('auctions.show', $auction)));

        // The one event a live bidder must see instantly — the public
        // auction.{id} channel says the price moved, but only this says it was
        // YOUR bid that got beaten.
        $this->broadcastPersonal($user, $auction, 'outbid', [
            'new_price' => dinars($newPriceCentimes),
        ]);
    }

    public function auctionWon(User $user, Auction $auction): void
    {
        $params = [
            'auction' => $auction->localizedTitle(),
            'amount' => dzd_text((int) $auction->final_price),
            'days' => $auction->finalPaymentDeadlineDays(),
        ];
        $user->notify(new AuctionEventNotification('auction_won', $params, route('auctions.show', $auction)));

        $this->broadcastPersonal($user, $auction, 'auction_won', [
            'final_price' => dinars((int) $auction->final_price),
            'deadline_days' => $auction->finalPaymentDeadlineDays(),
        ]);
    }

    public function auctionLost(User $user, Auction $auction): void
    {
        $params = ['auction' => $auction->localizedTitle()];
        $user->notify(new AuctionEventNotification('auction_lost', $params, route('auctions.show', $auction)));

        $this->broadcastPersonal($user, $auction, 'auction_lost');
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
            'amount' => dzd_text($amountCentimes),
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

    // ===== Appeals workflow (§ الطعون) — one handoff per method =====

    /** New appeal filed → notify the platform admins who triage it. */
    public function appealSubmitted(Appeal $appeal): void
    {
        $appeal->loadMissing('auction');
        $params = ['auction' => $appeal->auction?->localizedTitle() ?? ''];
        foreach ($this->platformAdmins() as $admin) {
            $admin->notify(new AuctionEventNotification('appeal_submitted', $params, route('admin.appeals.index')));
        }
    }

    /** Admin forwarded the appeal → notify the organising entity's account. */
    public function appealForwarded(Appeal $appeal): void
    {
        $appeal->loadMissing('auction.entity.account');
        $account = $appeal->auction?->entity?->account;
        if (! $account) {
            return; // entity has no institutional login provisioned yet
        }
        $params = ['auction' => $appeal->auction?->localizedTitle() ?? ''];
        $account->notify(new AuctionEventNotification('appeal_forwarded', $params, route('admin.appeals.index')));
    }

    /** Entity decided → notify the platform admins who confirm the decision. */
    public function appealEntityDecided(Appeal $appeal): void
    {
        $appeal->loadMissing('auction');
        $params = [
            'auction' => $appeal->auction?->localizedTitle() ?? '',
            'decision' => $appeal->entity_decision?->label() ?? '',
        ];
        foreach ($this->platformAdmins() as $admin) {
            $admin->notify(new AuctionEventNotification('appeal_entity_decided', $params, route('admin.appeals.index')));
        }
    }

    /** Final decision confirmed → notify the citizen with the public status. */
    public function appealResolved(Appeal $appeal): void
    {
        $appeal->loadMissing('user');
        if (! $appeal->user) {
            return;
        }
        $params = ['status' => $appeal->status->publicLabel()];
        $appeal->user->notify(new AuctionEventNotification('appeal_updated', $params, route('citizen.appeals')));
    }

    /**
     * Admin referred an auction report → notify the organising entity's account
     * so it appears in their reports module. Mirrors appealForwarded().
     */
    public function auctionReportReferred(AuctionReport $report): void
    {
        $report->loadMissing('auction.entity.account');
        $account = $report->auction?->entity?->account;
        if (! $account) {
            return; // entity has no institutional login provisioned yet
        }
        $params = ['auction' => $report->auction?->localizedTitle() ?? ''];
        $account->notify(new AuctionEventNotification('auction_report_referred', $params, route('admin.auction-reports.index')));
    }

    /**
     * Push a realtime nudge to the participant's private auction channel
     * (`auction.{id}.user.{id}`), alongside the durable notification row.
     *
     * Best-effort by design: broadcasting is an inline HTTP call to Reverb, and a
     * websocket server being down must never fail the bid or payment that
     * triggered it — the notification row and the email still went out, and the
     * client falls back to polling.
     *
     * @param  array<string, mixed>  $payload
     */
    private function broadcastPersonal(User $user, ?Auction $auction, string $type, array $payload = []): void
    {
        if (! $auction) {
            return;
        }

        try {
            PersonalAuctionEvent::dispatch($user->id, $auction->id, $type, $payload);
        } catch (\Throwable $e) {
            Log::warning('Personal auction broadcast failed', [
                'user_id' => $user->id,
                'auction_id' => $auction->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Platform-wide admins (entity_id null) who triage/confirm appeals.
     *
     * @return Collection<int, User>
     */
    private function platformAdmins(): Collection
    {
        return User::role(UserRole::SUPER_ADMIN->value)
            ->whereNull('entity_id')
            ->get();
    }
}
